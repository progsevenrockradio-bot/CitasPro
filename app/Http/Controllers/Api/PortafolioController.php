<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portafolio;
use App\Models\Profesional;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PortafolioController
 *
 * Gestión del portafolio de trabajos realizados por un Profesional.
 * Soporta imágenes y vídeos. Almacenamiento en disco local o cloud (S3/DO).
 *
 * Rutas:
 *   GET    /api/portafolio/{profesionalId}          → Listar portafolio público
 *   POST   /api/portafolio/{profesionalId}/subir    → Subir archivo (auth)
 *   PATCH  /api/portafolio/{id}                    → Actualizar metadata (auth)
 *   DELETE /api/portafolio/{id}                    → Eliminar entrada (auth)
 *   POST   /api/portafolio/reordenar               → Cambiar orden visual (auth)
 */
class PortafolioController extends Controller
{
    public function __construct(
        private readonly StorageService $storage
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/portafolio/{profesionalId}
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lista el portafolio público de un profesional.
     * Ruta pública (sin autenticación necesaria).
     *
     * Parámetros opcionales de query:
     *   ?tipo=imagen|video|antes_despues   → Filtrar por tipo
     *   ?destacado=1                       → Solo los destacados
     *   ?servicio_id=5                     → Filtrar por servicio
     *   ?per_page=12                       → Items por página (default 12)
     */
    public function index(Request $request, int $profesionalId): JsonResponse
    {
        $profesional = Profesional::where('activo', true)->find($profesionalId);

        if (!$profesional) {
            return response()->json(['success' => false, 'message' => 'Profesional no encontrado.'], 404);
        }

        $query = Portafolio::where('profesional_id', $profesionalId)
            ->where('publico', true)
            ->with('servicio:id,nombre');

        // Filtros opcionales
        if ($tipo = $request->query('tipo')) {
            $query->where('tipo', $tipo);
        }

        if ($request->boolean('destacado')) {
            $query->where('destacado', true);
        }

        if ($servicioId = $request->query('servicio_id')) {
            $query->where('servicio_id', $servicioId);
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $portafolios */
        $portafolios = $query
            ->orderByDesc('destacado')
            ->orderBy('orden')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 12));

        return response()->json([
            'success'      => true,
            'profesional'  => [
                'id'     => $profesional->id,
                'nombre' => $profesional->nombre_completo,
                'titulo' => $profesional->titulo,
            ],
            'portafolio'   => $portafolios->through(fn ($p) => $this->formatearPortafolio($p)),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/portafolio/{profesionalId}/subir
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Sube un archivo al portafolio del profesional.
     *
     * Body (multipart/form-data):
     *   archivo*         → Imagen (jpg,png,webp,gif) o Video (mp4,mov,webm) — Req.
     *   titulo           → Título descriptivo del trabajo
     *   descripcion      → Descripción detallada
     *   servicio_id      → ID del servicio relacionado
     *   destacado        → boolean (1|0) si aparece primero
     *   tipo             → 'imagen' | 'video' | 'antes_despues'
     *   archivo_antes    → Segunda imagen para tipo 'antes_despues'
     *
     * Respuesta:
     *   { success, message, portafolio: { id, url, url_miniatura, ... } }
     */
    public function subir(Request $request, int $profesionalId): JsonResponse
    {
        $profesional = Profesional::where('activo', true)->find($profesionalId);

        if (!$profesional) {
            return response()->json(['success' => false, 'message' => 'Profesional no encontrado.'], 404);
        }

        // ── Validación ───────────────────────────────────────────────────────
        $request->validate(array_merge(
            StorageService::reglasValidacion(),
            [
                'titulo'       => ['nullable', 'string', 'max:200'],
                'descripcion'  => ['nullable', 'string', 'max:1000'],
                'servicio_id'  => ['nullable', 'integer', 'exists:servicios,id'],
                'destacado'    => ['nullable', 'boolean'],
                'tipo'         => ['nullable', 'in:imagen,video,antes_despues'],
                'archivo_antes'=> [
                    'nullable',
                    'file',
                    'image',
                    'max:' . (10 * 1024),
                    'mimes:jpg,jpeg,png,webp',
                ],
            ]
        ), [
            'archivo.required' => 'Debes adjuntar un archivo (imagen o video).',
            'archivo.max'      => 'El archivo no puede superar los 100 MB.',
            'archivo.mimes'    => 'Formato no soportado. Usa JPG, PNG, WEBP, MP4, MOV o WEBM.',
        ]);

        DB::beginTransaction();

        try {
            // ── Subir archivo principal ──────────────────────────────────────
            $archivoInfo = $this->storage->subirPortafolio(
                $request->file('archivo'),
                $profesionalId
            );

            // ── Subir imagen "antes" si es antes/después ─────────────────────
            $rutaAntes = null;
            if ($request->hasFile('archivo_antes') && $request->tipo === 'antes_despues') {
                $antesInfo = $this->storage->subirPortafolio(
                    $request->file('archivo_antes'),
                    $profesionalId
                );
                $rutaAntes = $antesInfo['ruta'];
            }

            // ── Determinar tipo automáticamente si no se especificó ──────────
            $tipo = $request->input('tipo');
            if (!$tipo) {
                $tipo = match ($archivoInfo['tipo']) {
                    'video'  => 'video',
                    default  => $rutaAntes ? 'antes_despues' : 'imagen',
                };
            }

            // ── Calcular el orden (al final del portafolio actual) ───────────
            $ultimoOrden = Portafolio::where('profesional_id', $profesionalId)->max('orden') ?? 0;

            // ── Crear registro en BD ─────────────────────────────────────────
            $portafolio = Portafolio::create([
                'profesional_id'    => $profesionalId,
                'servicio_id'       => $request->input('servicio_id'),
                'titulo'            => $request->input('titulo'),
                'descripcion'       => $request->input('descripcion'),
                'imagen'            => $archivoInfo['ruta'],
                'imagen_miniatura'  => $archivoInfo['ruta_miniatura'],
                'tipo'              => $tipo,
                'imagen_antes'      => $rutaAntes,
                'destacado'         => $request->boolean('destacado', false),
                'publico'           => true,
                'orden'             => $ultimoOrden + 1,
            ]);

            DB::commit();

            Log::info("PortafolioController: Archivo subido", [
                'profesional_id' => $profesionalId,
                'portafolio_id'  => $portafolio->id,
                'tipo'           => $tipo,
                'disco'          => $archivoInfo['disco'],
                'tamanio_kb'     => $archivoInfo['tamanio_kb'],
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Archivo subido correctamente al portafolio.',
                'portafolio' => $this->formatearPortafolio($portafolio, $archivoInfo),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error("PortafolioController: Error al subir archivo", [
                'profesional_id' => $profesionalId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo. Inténtalo de nuevo.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PATCH /api/portafolio/{id}
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Actualiza la metadata de una entrada del portafolio (sin cambiar el archivo).
     *
     * Body (JSON):
     *   titulo, descripcion, servicio_id, destacado, publico, orden
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $portafolio = Portafolio::find($id);

        if (!$portafolio) {
            return response()->json(['success' => false, 'message' => 'Entrada no encontrada.'], 404);
        }

        $request->validate([
            'titulo'       => ['nullable', 'string', 'max:200'],
            'descripcion'  => ['nullable', 'string', 'max:1000'],
            'servicio_id'  => ['nullable', 'integer', 'exists:servicios,id'],
            'destacado'    => ['nullable', 'boolean'],
            'publico'      => ['nullable', 'boolean'],
            'orden'        => ['nullable', 'integer', 'min:0'],
        ]);

        $portafolio->update($request->only([
            'titulo', 'descripcion', 'servicio_id', 'destacado', 'publico', 'orden',
        ]));

        return response()->json([
            'success'    => true,
            'message'    => 'Portafolio actualizado.',
            'portafolio' => $this->formatearPortafolio($portafolio->fresh()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /api/portafolio/{id}
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Elimina una entrada del portafolio y sus archivos del disco.
     */
    public function eliminar(int $id): JsonResponse
    {
        $portafolio = Portafolio::find($id);

        if (!$portafolio) {
            return response()->json(['success' => false, 'message' => 'Entrada no encontrada.'], 404);
        }

        DB::beginTransaction();

        try {
            // Eliminar archivos del disco
            $this->storage->eliminarPortafolio($portafolio->imagen, $portafolio->imagen_miniatura);

            if ($portafolio->imagen_antes) {
                $this->storage->eliminarPortafolio($portafolio->imagen_antes);
            }

            // Soft-delete del registro
            $portafolio->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrada eliminada del portafolio.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("PortafolioController: Error al eliminar {$id}: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al eliminar.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/portafolio/reordenar
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Actualiza el orden visual de múltiples entradas en una sola operación.
     *
     * Body (JSON):
     *   { "orden": [ {"id": 3, "orden": 1}, {"id": 7, "orden": 2}, ... ] }
     */
    public function reordenar(Request $request): JsonResponse
    {
        $request->validate([
            'orden'          => ['required', 'array', 'min:1'],
            'orden.*.id'     => ['required', 'integer', 'exists:portafolios,id'],
            'orden.*.orden'  => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->orden as $item) {
                Portafolio::where('id', $item['id'])
                    ->update(['orden' => $item['orden']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado correctamente.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Formatea un Portafolio para la respuesta JSON.
     * Enriquece con URLs públicas generadas por el StorageService.
     */
    private function formatearPortafolio(Portafolio $p, ?array $archivoInfo = null): array
    {
        return [
            'id'              => $p->id,
            'titulo'          => $p->titulo,
            'descripcion'     => $p->descripcion,
            'tipo'            => $p->tipo,
            'url'             => $p->imagen ? $this->storage->url($p->imagen) : null,
            'url_miniatura'   => $p->imagen_miniatura ? $this->storage->url($p->imagen_miniatura) : null,
            'url_antes'       => $p->imagen_antes ? $this->storage->url($p->imagen_antes) : null,
            'servicio'        => $p->servicio?->nombre,
            'servicio_id'     => $p->servicio_id,
            'destacado'       => $p->destacado,
            'publico'         => $p->publico,
            'orden'           => $p->orden,
            'disco'           => $archivoInfo['disco'] ?? $this->storage->disco(),
            'tamanio_kb'      => $archivoInfo['tamanio_kb'] ?? null,
            'nombre_original' => $archivoInfo['nombre_original'] ?? null,
            'creado_en'       => $p->created_at?->toIso8601String(),
        ];
    }
}
