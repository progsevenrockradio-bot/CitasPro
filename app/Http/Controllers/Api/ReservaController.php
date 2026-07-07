<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReservaController extends Controller
{
    /**
     * Crea una cita desde el frontend del paciente.
     * 
     * Body: {
     *   "profesional_id": 1,
     *   "servicio_id": 2,
     *   "fecha": "2026-06-30",
     *   "hora": "15:00",
     *   "cliente": {
     *       "nombre": "Juan",
     *       "apellido": "Pérez",
     *       "telefono": "+34600123123"
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profesional_id' => 'required|exists:profesionales,id',
            'servicio_id' => 'required|exists:servicios,id',
            'fecha' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
            'cliente.nombre' => 'required|string',
            'cliente.apellido' => 'required|string',
            'cliente.telefono' => 'required|string',
        ]);

        $servicio = Servicio::findOrFail($validated['servicio_id']);
        
        // 1. Buscar o Crear Cliente por teléfono
        $cliente = Cliente::firstOrCreate(
            ['telefono' => $validated['cliente']['telefono']],
            [
                'nombre' => $validated['cliente']['nombre'],
                'apellido' => $validated['cliente']['apellido']
            ]
        );

        // 2. Crear Cita
        // Sumar duración para calcular hora de fin
        $horaInicio = $validated['hora'] . ':00';
        $horaFin = \Carbon\Carbon::parse($horaInicio)->addMinutes($servicio->duracion_min)->format('H:i:s');

        $cita = Cita::create([
            'codigo_referencia' => 'RES-' . strtoupper(uniqid()),
            'negocio_id' => $servicio->negocio_id,
            'cliente_id' => $cliente->id,
            'profesional_id' => $validated['profesional_id'],
            'servicio_id' => $servicio->id,
            'fecha' => $validated['fecha'],
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'duracion_min' => $servicio->duracion_min,
            'estado' => 'confirmada', // Como es MVP asumimos que se confirma de una
            'precio_total' => $servicio->precio,
            'type' => $servicio->negocio?->tipo_clinica ?? 'general',
        ]);

        // Disparar evento para notificar al profesional por Telegram y al cliente
        event(new \App\Events\CitaCreada($cita));

        return response()->json([
            'success' => true,
            'message' => 'Reserva creada con éxito',
            'cita' => $cita
        ]);
    }
}
