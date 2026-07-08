<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resena;
use App\Models\Negocio;
use App\Models\Profesional;
use App\Models\Cita;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ResenaController extends Controller
{
    /**
     * Obtener reseñas de un negocio específico.
     */
    public function porNegocio(Negocio $negocio): JsonResponse
    {
        $resenas = Resena::with('cliente:id,nombre,apellido,foto')
            ->where('negocio_id', $negocio->id)
            ->activo()
            ->latest()
            ->paginate(15);
            
        return response()->json($resenas);
    }

    /**
     * Obtener reseñas de un profesional específico.
     */
    public function porProfesional(Profesional $profesional): JsonResponse
    {
        $resenas = Resena::with('cliente:id,nombre,apellido,foto')
            ->where('profesional_id', $profesional->id)
            ->activo()
            ->latest()
            ->paginate(15);
            
        return response()->json($resenas);
    }

    /**
     * Crear una nueva reseña.
     * Solo para clientes autenticados.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Verificar que sea un cliente
        if (!$user instanceof Cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los clientes pueden dejar reseñas.'
            ], 403);
        }

        $validated = $request->validate([
            'profesional_id' => 'required|exists:profesionales,id',
            'cita_id'        => 'nullable|exists:citas,id',
            'calificacion'   => 'required|integer|min:1|max:5',
            'comentario'     => 'nullable|string|max:1000',
        ]);

        $profesional = Profesional::findOrFail($validated['profesional_id']);

        // Verificar si la cita pertenece al cliente (si se envía)
        if (!empty($validated['cita_id'])) {
            $cita = Cita::findOrFail($validated['cita_id']);
            if ($cita->cliente_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cita no corresponde a este cliente.'
                ], 403);
            }
            
            // Verificar si el cliente ya dejó reseña para esta cita
            $reseñaExistente = Resena::where('cita_id', $cita->id)->first();
            if ($reseñaExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has dejado una reseña para esta cita.'
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $resena = Resena::create([
                'negocio_id'     => $profesional->negocio_id,
                'profesional_id' => $profesional->id,
                'cliente_id'     => $user->id,
                'cita_id'        => $validated['cita_id'] ?? null,
                'calificacion'   => $validated['calificacion'],
                'comentario'     => $validated['comentario'] ?? null,
                'activo'         => true,
            ]);

            // Recalcular promedio y total en el profesional
            $estadisticas = Resena::where('profesional_id', $profesional->id)
                ->activo()
                ->selectRaw('count(*) as total, avg(calificacion) as promedio')
                ->first();

            $profesional->update([
                'total_resenas'         => $estadisticas->total ?? 0,
                'calificacion_promedio' => $estadisticas->promedio ?? 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reseña guardada exitosamente.',
                'data'    => $resena->load('cliente:id,nombre,apellido,foto')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la reseña.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las reseñas del negocio para el dueño/profesional autenticado.
     */
    public function dashboardIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 401);
        }

        $negocioId = $user->negocio_id;
        if (!$negocioId) {
            return response()->json(['success' => false, 'message' => 'Negocio no encontrado.'], 404);
        }

        $resenas = Resena::with([
            'cliente:id,nombre,apellido,foto',
            'profesional:id,nombre,apellido',
            'cita.servicio:id,nombre'
        ])
        ->where('negocio_id', $negocioId)
        ->latest()
        ->paginate(15);

        // Calcular estadísticas rápidas
        $promedio = Resena::where('negocio_id', $negocioId)->activo()->avg('calificacion') ?? 0;
        $total = Resena::where('negocio_id', $negocioId)->count();
        $ocultas = Resena::where('negocio_id', $negocioId)->where('activo', false)->count();

        return response()->json([
            'success' => true,
            'data' => $resenas,
            'stats' => [
                'promedio' => round($promedio, 1),
                'total' => $total,
                'ocultas' => $ocultas
            ]
        ]);
    }

    /**
     * Activar o desactivar (ocultar) una reseña del negocio.
     */
    public function toggleActivo(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 401);
        }

        $resena = Resena::findOrFail($id);
        
        // Verificar propiedad
        if ($resena->negocio_id !== $user->negocio_id) {
            return response()->json(['success' => false, 'message' => 'Acción no permitida.'], 403);
        }

        try {
            DB::beginTransaction();

            $resena->activo = !$resena->activo;
            $resena->save();

            // Recalcular estadísticas del profesional
            if ($resena->profesional_id) {
                $estadisticas = Resena::where('profesional_id', $resena->profesional_id)
                    ->activo()
                    ->selectRaw('count(*) as total, avg(calificacion) as promedio')
                    ->first();

                $profesional = Profesional::find($resena->profesional_id);
                if ($profesional) {
                    $profesional->update([
                        'total_resenas' => $estadisticas->total ?? 0,
                        'calificacion_promedio' => $estadisticas->promedio ?? 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $resena->activo ? 'Reseña aprobada y visible públicamente.' : 'Reseña oculta correctamente.',
                'activo' => $resena->activo
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado de la reseña.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
