<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * StorageService — Capa de abstracción para subida de archivos.
 *
 * Diseñado para ser agnóstico al disco:
 *   • Desarrollo: disco 'portafolios' (local en storage/app/public/portafolios)
 *   • Producción:  cambiar FILESYSTEM_DISK_PORTAFOLIOS=s3 o =do en .env
 *
 * La URL pública se genera correctamente independientemente del disco activo.
 *
 * NOTA: Intervention Image es opcional. Si no está instalado, se omite el resize.
 * Para instalarlo: composer require intervention/image
 */
class StorageService
{
    // ── Configuración de tipos de archivo ─────────────────────────────────────

    private const MIME_IMAGENES = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif',
    ];

    private const MIME_VIDEOS = [
        'video/mp4', 'video/quicktime', 'video/mpeg', 'video/webm',
    ];

    // Tamaños máximos por tipo (en KB)
    private const MAX_SIZE_IMAGEN_KB = 10 * 1024;   // 10 MB
    private const MAX_SIZE_VIDEO_KB  = 100 * 1024;  // 100 MB

    // Dimensiones máximas para imágenes (px)
    private const MAX_WIDTH_PX  = 2048;
    private const MAX_HEIGHT_PX = 2048;

    // Dimensión de la miniatura
    private const THUMB_WIDTH_PX = 400;

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos públicos
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Sube un archivo de portafolio (imagen o video) al disco configurado.
     *
     * @param  UploadedFile $archivo       Archivo subido por el usuario
     * @param  int          $profesionalId ID del profesional (para organizar carpetas)
     * @return array{
     *   ruta: string,
     *   ruta_miniatura: string|null,
     *   url: string,
     *   url_miniatura: string|null,
     *   tipo: string,
     *   nombre_original: string,
     *   tamanio_kb: int,
     *   mime: string,
     *   disco: string,
     * }
     */
    public function subirPortafolio(UploadedFile $archivo, int $profesionalId): array
    {
        $disco    = $this->disco();
        $tipo     = $this->detectarTipo($archivo);
        $carpeta  = "profesional-{$profesionalId}/" . now()->format('Y/m');
        $nombre   = $this->generarNombreUnico($archivo->getClientOriginalExtension());

        // ── Subir archivo principal ──────────────────────────────────────────
        $ruta = $archivo->storeAs($carpeta, $nombre, ['disk' => $disco]);

        if ($ruta === false) {
            throw new \RuntimeException("No se pudo guardar el archivo en el disco '{$disco}'.");
        }

        // ── Generar miniatura (solo para imágenes) ───────────────────────────
        $rutaMiniatura = null;
        if ($tipo === 'imagen' && $this->interventionDisponible()) {
            $rutaMiniatura = $this->generarMiniatura($archivo, $carpeta, $nombre, $disco);
        }

        return [
            'ruta'            => $ruta,
            'ruta_miniatura'  => $rutaMiniatura,
            'url'             => $this->url($ruta, $disco),
            'url_miniatura'   => $rutaMiniatura ? $this->url($rutaMiniatura, $disco) : null,
            'tipo'            => $tipo,
            'nombre_original' => $archivo->getClientOriginalName(),
            'tamanio_kb'      => (int) ceil($archivo->getSize() / 1024),
            'mime'            => $archivo->getMimeType(),
            'disco'           => $disco,
        ];
    }

    /**
     * Elimina un archivo del disco de portafolios.
     * Si tiene miniatura, también la elimina.
     */
    public function eliminarPortafolio(string $ruta, ?string $rutaMiniatura = null): bool
    {
        $disco    = $this->disco();
        $eliminado = Storage::disk($disco)->delete($ruta);

        if ($rutaMiniatura && Storage::disk($disco)->exists($rutaMiniatura)) {
            Storage::disk($disco)->delete($rutaMiniatura);
        }

        return $eliminado;
    }

    /**
     * Genera la URL pública de un archivo.
     * En disco local → /storage/portafolios/...
     * En S3/DO       → https://bucket.s3.amazonaws.com/...
     */
    public function url(string $ruta, ?string $disco = null): string
    {
        return Storage::disk($disco ?? $this->disco())->url($ruta);
    }

    /**
     * Comprueba si un archivo existe en el disco.
     */
    public function existe(string $ruta): bool
    {
        return Storage::disk($this->disco())->exists($ruta);
    }

    /**
     * Devuelve el disco de portafolios activo (según .env).
     */
    public function disco(): string
    {
        return config('filesystems.default_portafolios', env('FILESYSTEM_DISK_PORTAFOLIOS', 'portafolios'));
    }

    /**
     * Reglas de validación para archivos de portafolio.
     * Listas para usar directamente en $request->validate([]).
     */
    public static function reglasValidacion(): array
    {
        $maxImagenKb = self::MAX_SIZE_IMAGEN_KB;
        $maxVideoKb  = self::MAX_SIZE_VIDEO_KB;

        $mimesImagenes = implode(',', array_map(
            fn ($m) => str_replace('image/', '', $m),
            self::MIME_IMAGENES
        ));

        $mimesVideos = 'mp4,mov,mpeg,webm';

        return [
            'archivo' => [
                'required',
                'file',
                "max:{$maxImagenKb}",
                "mimes:{$mimesImagenes},{$mimesVideos}",
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados
    // ─────────────────────────────────────────────────────────────────────────

    private function detectarTipo(UploadedFile $archivo): string
    {
        $mime = $archivo->getMimeType();

        if (in_array($mime, self::MIME_IMAGENES, true)) {
            return 'imagen';
        }

        if (in_array($mime, self::MIME_VIDEOS, true)) {
            return 'video';
        }

        return 'otro';
    }

    private function generarNombreUnico(string $extension): string
    {
        return Str::uuid() . '.' . strtolower($extension);
    }

    /**
     * Genera miniatura redimensionada usando Intervention Image.
     * Requiere: composer require intervention/image
     */
    private function generarMiniatura(
        UploadedFile $archivo,
        string       $carpeta,
        string       $nombreOriginal,
        string       $disco
    ): ?string {
        try {
            $nombreThumb = 'thumb_' . $nombreOriginal;
            $rutaThumb   = $carpeta . '/' . $nombreThumb;

            $imagen = Image::make($archivo->getRealPath())
                ->resize(self::THUMB_WIDTH_PX, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize(); // No ampliar si es más pequeña
                });

            Storage::disk($disco)->put(
                $rutaThumb,
                $imagen->encode(null, 85)->getEncoded(), // Calidad 85%
                'public'
            );

            return $rutaThumb;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                "StorageService: No se pudo generar miniatura para {$nombreOriginal}: " . $e->getMessage()
            );
            return null;
        }
    }

    private function interventionDisponible(): bool
    {
        return class_exists(\Intervention\Image\Facades\Image::class);
    }
}
