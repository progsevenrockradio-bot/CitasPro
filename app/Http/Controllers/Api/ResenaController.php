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
}
