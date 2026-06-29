<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profesional;
use Illuminate\Http\JsonResponse;

class PacienteController extends Controller
{
    /**
     * Devuelve el portafolio público del profesional (Datos + Servicios activos)
     */
    public function portafolio($id): JsonResponse
    {
        $profesional = Profesional::with('negocio:id,nombre,slug')->find($id);

        if (!$profesional) {
            return response()->json(['success' => false, 'message' => 'Profesional no encontrado'], 404);
        }

        $servicios = \App\Models\Servicio::where('negocio_id', $profesional->negocio_id)
            ->where('activo', true)
            ->get();

        return response()->json([
            'success' => true,
            'profesional' => [
                'id' => $profesional->id,
                'nombre' => $profesional->nombre,
                'apellido' => $profesional->apellido,
                'especialidad' => $profesional->especialidad,
                'negocio' => $profesional->negocio,
            ],
            'servicios' => $servicios
        ]);
    }
}
