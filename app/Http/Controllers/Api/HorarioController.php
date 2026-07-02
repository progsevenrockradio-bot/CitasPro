<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HorarioController extends Controller
{
    /**
     * Obtiene el horario actual del profesional.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profesional = $user instanceof Profesional ? $user : null;

        if (!$profesional) {
            return response()->json(['success' => false, 'message' => 'No tienes un perfil activo.'], 403);
        }

        // Si es null, devolvemos un horario por defecto (L-V, 09:00-19:00)
        $horario = $profesional->horario_disponible ?? $this->defaultHorario();

        return response()->json([
            'success' => true,
            'horario' => $horario
        ]);
    }

    /**
     * Actualiza el horario del profesional.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $profesional = $user instanceof Profesional ? $user : null;

        if (!$profesional) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos.'], 403);
        }

        $validated = $request->validate([
            'horario' => 'required|array'
        ]);

        $profesional->update([
            'horario_disponible' => $validated['horario']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Horario actualizado correctamente.',
            'horario' => $profesional->horario_disponible
        ]);
    }

    /**
     * Genera un horario por defecto para mostrar en el frontend si no hay nada guardado.
     */
    private function defaultHorario(): array
    {
        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $default = [];

        foreach ($dias as $dia) {
            $default[$dia] = [
                'activo' => !in_array($dia, ['sabado', 'domingo']),
                'inicio' => '09:00',
                'fin' => '19:00'
            ];
        }

        return $default;
    }
}
