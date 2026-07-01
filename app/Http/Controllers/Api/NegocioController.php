<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Negocio;
use App\Models\Profesional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NegocioController extends Controller
{
    /**
     * Obtener la configuración actual del negocio del profesional autenticado.
     */
    public function show(Request $request): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        $negocio = Negocio::with('categoria')->findOrFail($profesional->negocio_id);

        return response()->json($negocio);
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
        ]);

        $negocio = Negocio::findOrFail($profesional->negocio_id);
        $negocio->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Configuración del negocio actualizada correctamente.',
            'negocio' => $negocio
        ]);
    }

    private function getProfesional(Request $request): ?Profesional
    {
        $user = $request->user();
        if ($user instanceof Profesional) {
            return $user;
        }
        return null;
    }
}
