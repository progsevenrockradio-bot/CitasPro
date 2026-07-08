<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Negocio;
use Illuminate\Http\JsonResponse;

class DirectorioController extends Controller
{
    /**
     * GET /api/directorio
     * Lista paginada de negocios activos para la vitrina pública de CitasPro.
     */
    public function index(): JsonResponse
    {
        $negocios = Negocio::with('categoria')
            ->where('activo', true)
            ->where('booking_activo', true)
            ->when(request('q'), fn($q, $search) =>
                $q->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', "%{$search}%")
                          ->orWhere('ciudad', 'like', "%{$search}%")
                          ->orWhere('descripcion', 'like', "%{$search}%");
                })
            )
            ->when(request('categoria_id'), fn($q, $cat) =>
                $q->where('categoria_id', $cat)
            )
            ->when(request('ciudad'), fn($q, $ciudad) =>
                $q->where('ciudad', 'like', "%{$ciudad}%")
            )
            ->select([
                'id', 'nombre', 'slug', 'descripcion', 'logo',
                'categoria_id', 'ciudad', 'pais', 'telefono',
                'horario_apertura', 'duracion_turno_min', 'booking_activo',
            ])
            ->orderBy('nombre')
            ->paginate(20);

        $negocios->getCollection()->transform(function ($negocio) {
            $negocio->logo = $negocio->logo ? asset('storage/' . $negocio->logo) : null;
            return $negocio;
        });

        return response()->json([
            'success' => true,
            'data' => $negocios,
        ]);
    }

    /**
     * GET /api/directorio/{slug}
     * Perfil público de un negocio con sus servicios y profesionales activos.
     */
    public function show(Negocio $negocio): JsonResponse
    {
        if (!$negocio->activo || !$negocio->booking_activo) {
            return response()->json([
                'success' => false,
                'message' => 'Este negocio no está disponible para reservas online.',
            ], 404);
        }

        $negocio->load([
            'categoria',
            'profesionales' => fn($q) => $q->where('activo', true)->where('aceptar_online', true),
            'servicios' => fn($q) => $q->where('activo', true)->orderBy('orden'),
        ]);

        return response()->json([
            'success' => true,
            'negocio' => [
                'id' => $negocio->id,
                'nombre' => $negocio->nombre,
                'slug' => $negocio->slug,
                'descripcion' => $negocio->descripcion,
                'logo' => $negocio->logo ? asset('storage/' . $negocio->logo) : null,
                'cover_imagen' => $negocio->cover_imagen ? asset('storage/' . $negocio->cover_imagen) : null,
                'telefono' => $negocio->telefono,
                'whatsapp' => $negocio->whatsapp,
                'direccion' => $negocio->direccion,
                'ciudad' => $negocio->ciudad,
                'horario_apertura' => $negocio->horario_apertura,
                'duracion_turno_min' => $negocio->duracion_turno_min,
                'booking_mensaje' => $negocio->booking_mensaje,
                'categoria' => $negocio->categoria?->only('id', 'nombre', 'icono', 'color_hex'),
                'public_booking_url' => $negocio->public_booking_url,
            ],
            'profesionales' => $negocio->profesionales->map(fn($p) => [
                'id' => $p->id,
                'nombre_completo' => $p->nombre_completo,
                'nombre' => $p->nombre,
                'apellido' => $p->apellido,
                'titulo' => $p->titulo,
                'bio' => $p->bio,
                'foto' => $p->foto,
                'calificacion_promedio' => $p->calificacion_promedio,
            ]),
            'servicios' => $negocio->servicios->map(fn($s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'descripcion' => $s->descripcion,
                'duracion_min' => $s->duracion_min,
                'precio' => $s->precio,
                'moneda' => $s->moneda,
                'precio_desde' => $s->precio_desde,
                'imagen' => $s->imagen,
            ]),
        ]);
    }
}
