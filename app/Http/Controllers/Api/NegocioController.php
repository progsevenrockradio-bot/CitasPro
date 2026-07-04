<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Negocio;
use App\Models\Profesional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NegocioController extends Controller
{
    /**
     * Obtener la configuración actual del negocio del profesional autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        $negocio = Negocio::with('categoria')->findOrFail($profesional->negocio_id);

        return response()->json([
            'success' => true,
            'negocio' => [
                'id' => $negocio->id,
                'nombre' => $negocio->nombre,
                'descripcion' => $negocio->descripcion,
                'categoria_id' => $negocio->categoria_id,
                'es_medico' => (bool) $negocio->es_medico,
                'telefono' => $negocio->telefono,
                'whatsapp' => $negocio->whatsapp,
                'email' => $negocio->email,
                'sitio_web' => $negocio->sitio_web,
                'direccion' => $negocio->direccion,
                'ciudad' => $negocio->ciudad,
                'pais' => $negocio->pais,
                'horario_apertura' => $negocio->horario_apertura,
                'duracion_turno_min' => $negocio->duracion_turno_min,
                'plan' => $negocio->plan,
            ]
        ]);
    }

    /**
     * Actualizar la configuración del negocio.
     * Solo disponible para el rol 'dueño' o 'admin' del negocio.
     */
    public function update(Request $request): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        // Solo el dueño o admin pueden modificar el negocio
        if (!in_array($profesional->rol, ['dueño', 'admin'])) {
            return response()->json([
                'message' => 'No tienes permisos para modificar el negocio. Solo el dueño o admin puede hacerlo.'
            ], 403);
        }

        $validated = $request->validate([
            'nombre'                    => 'sometimes|string|max:150',
            'descripcion'               => 'sometimes|nullable|string|max:500',
            'telefono'                  => 'sometimes|nullable|string|max:20',
            'whatsapp'                  => 'sometimes|nullable|string|max:20',
            'email'                     => 'sometimes|nullable|email|max:100',
            'sitio_web'                 => 'sometimes|nullable|url|max:255',
            'direccion'                 => 'sometimes|nullable|string|max:255',
            'ciudad'                    => 'sometimes|nullable|string|max:100',
            'pais'                      => 'sometimes|nullable|string|max:100',
            'horario_apertura'          => 'sometimes|nullable|array',
            'duracion_turno_min'        => 'sometimes|integer|min:5|max:480',
            'anticipacion_min_reserva'  => 'sometimes|integer|min:0',
            'cancelacion_limite_horas'  => 'sometimes|integer|min:0',
            'es_medico'                 => 'sometimes|boolean',
        ]);

        $negocio = Negocio::findOrFail($profesional->negocio_id);
        $negocio->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Configuración del negocio actualizada correctamente.',
            'negocio' => $negocio
        ]);
    }

    /**
     * Eliminar el negocio y todos sus datos en cascada.
     * Solo disponible para el 'dueño' del negocio.
     */
    public function destroy(Request $request): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        // Solo el dueño original puede dar de baja el negocio completo
        if ($profesional->rol !== 'dueño') {
            return response()->json([
                'message' => 'No tienes permisos para eliminar este negocio. Solo el dueño de la cuenta puede hacerlo.'
            ], 403);
        }

        $negocio = Negocio::findOrFail($profesional->negocio_id);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($negocio, $request) {
                // 1. Borrar todas las citas del negocio
                \App\Models\Cita::where('negocio_id', $negocio->id)->delete();

                // 2. Borrar todos los pagos
                \App\Models\Pago::where('negocio_id', $negocio->id)->delete();

                // 3. Borrar todos los servicios
                \App\Models\Servicio::where('negocio_id', $negocio->id)->delete();

                // 4. Borrar portafolios
                \App\Models\Portafolio::where('negocio_id', $negocio->id)->delete();

                // 5. Revocar tokens de todos los profesionales antes de eliminarlos
                $profesionales = Profesional::where('negocio_id', $negocio->id)->get();
                foreach ($profesionales as $p) {
                    $p->tokens()->delete();
                    $p->delete();
                }

                // 6. Finalmente borrar el negocio
                $negocio->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Tu negocio y todos los datos asociados han sido eliminados de forma permanente de CitasPro.'
            ]);

        } catch (\Exception $e) {
            Log::error("Error al eliminar negocio ID {$negocio->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la baja de la cuenta. Inténtalo de nuevo.'
            ], 500);
        }
    }


    private function getProfesional(Request $request): ?Profesional
    {
        $user = $request->user();
        if ($user instanceof Profesional) {
            return $user;
        }
        
        // MODO DEMO para Super Admin
        if ($user instanceof \App\Models\User) {
            $profesional = Profesional::first();
            if ($profesional) {
                // Forzar permisos de dueño para que el admin pueda editar
                $profesional->rol = 'dueño'; 
                return $profesional;
            }
        }
        
        return null;
    }
}
