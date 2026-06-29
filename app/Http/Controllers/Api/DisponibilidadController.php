<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DisponibilidadController extends Controller
{
    /**
     * Calcula las horas disponibles para un profesional en un día específico.
     * Query: ?fecha=YYYY-MM-DD
     */
    public function index($id, Request $request): JsonResponse
    {
        $request->validate([
            'fecha' => 'required|date_format:Y-m-d'
        ]);

        $fecha = $request->input('fecha');
        
        // Simular un horario comercial de 09:00 a 19:00, con slots cada hora.
        // En Fase 4 esto se leerá de la tabla "Horarios".
        $horasDelDia = [
            '09:00', '10:00', '11:00', '12:00', 
            '13:00', '14:00', '15:00', '16:00', 
            '17:00', '18:00', '19:00'
        ];

        // Buscar citas confirmadas/pendientes/en_curso para ese día
        $citasOcupadas = Cita::where('profesional_id', $id)
            ->where('fecha', $fecha)
            ->whereIn('estado', ['pendiente', 'confirmada', 'en_curso'])
            ->pluck('hora_inicio')
            ->map(function($hora) {
                // Formatear de "09:00:00" a "09:00"
                return substr($hora, 0, 5);
            })
            ->toArray();

        // Filtrar
        $horasDisponibles = array_values(array_diff($horasDelDia, $citasOcupadas));

        // Si la fecha es hoy, filtrar horas pasadas
        if ($fecha === date('Y-m-d')) {
            $horaActual = date('H:i');
            $horasDisponibles = array_filter($horasDisponibles, function($hora) use ($horaActual) {
                return $hora > $horaActual;
            });
            $horasDisponibles = array_values($horasDisponibles); // reset keys
        }

        return response()->json([
            'success' => true,
            'fecha' => $fecha,
            'disponibles' => $horasDisponibles
        ]);
    }
}
