<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Negocio;
use App\Models\Profesional;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservaPublicaController extends Controller
{
    /**
     * GET /api/public/{slug}
     * Información pública del negocio para montar la página de reserva.
     */
    public function show(Negocio $negocio): JsonResponse
    {
        if (!$negocio->activo || !$negocio->booking_activo) {
            return response()->json([
                'success' => false,
                'message' => 'Las reservas online no están disponibles para este negocio.',
            ], 404);
        }

        $negocio->load([
            'categoria',
            'profesionales' => fn($q) => $q->where('activo', true)
                ->where('aceptar_online', true)
                ->select('id', 'negocio_id', 'nombre', 'apellido', 'titulo', 'bio', 'foto', 'calificacion_promedio'),
            'servicios' => fn($q) => $q->where('activo', true)->orderBy('orden')
                ->select('id', 'negocio_id', 'nombre', 'descripcion', 'duracion_min', 'precio', 'moneda', 'precio_desde', 'imagen'),
        ]);

        return response()->json([
            'success' => true,
            'negocio' => [
                'id'                  => $negocio->id,
                'nombre'              => $negocio->nombre,
                'slug'                => $negocio->slug,
                'descripcion'         => $negocio->descripcion,
                'logo'                => $negocio->logo,
                'cover_imagen'        => $negocio->cover_imagen,
                'telefono'            => $negocio->telefono,
                'whatsapp'            => $negocio->whatsapp,
                'direccion'           => $negocio->direccion,
                'ciudad'              => $negocio->ciudad,
                'horario_apertura'    => $negocio->horario_apertura,
                'duracion_turno_min'  => $negocio->duracion_turno_min,
                'booking_mensaje'     => $negocio->booking_mensaje,
                'categoria'           => $negocio->categoria?->only('id', 'nombre', 'icono', 'color_hex'),
            ],
            'profesionales' => $negocio->profesionales->map(fn($p) => [
                'id'                   => $p->id,
                'nombre_completo'      => $p->nombre_completo,
                'titulo'               => $p->titulo,
                'bio'                  => $p->bio,
                'foto'                 => $p->foto,
                'calificacion_promedio'=> $p->calificacion_promedio,
            ]),
            'servicios' => $negocio->servicios->map(fn($s) => [
                'id'          => $s->id,
                'nombre'      => $s->nombre,
                'descripcion' => $s->descripcion,
                'duracion_min'=> $s->duracion_min,
                'precio'      => $s->precio,
                'moneda'      => $s->moneda,
                'precio_desde'=> $s->precio_desde,
                'imagen'      => $s->imagen,
            ]),
        ]);
    }

    /**
     * GET /api/public/{slug}/disponibilidad?fecha=YYYY-MM-DD&profesional_id=1
     * Retorna los slots de hora disponibles para un profesional en una fecha.
     */
    public function disponibilidad(Negocio $negocio, Request $request): JsonResponse
    {
        $request->validate([
            'fecha'          => 'required|date_format:Y-m-d|after_or_equal:today',
            'profesional_id' => 'required|exists:profesionales,id',
        ]);

        $fecha         = $request->input('fecha');
        $profesionalId = $request->input('profesional_id');

        // Verificar que el profesional pertenece al negocio
        $profesional = Profesional::where('id', $profesionalId)
            ->where('negocio_id', $negocio->id)
            ->where('activo', true)
            ->firstOrFail();

        // Obtener horario del negocio para ese día de la semana
        $diaSemana = strtolower(Carbon::parse($fecha)->locale('es')->isoFormat('ddd')); // lun, mar, mié...
        $horario   = $negocio->horario_apertura[$diaSemana] ?? null;

        if (!$horario || empty($horario['inicio']) || empty($horario['fin'])) {
            // Horario por defecto si no está configurado
            $horaInicio = '09:00';
            $horaFin    = '19:00';
        } else {
            $horaInicio = $horario['inicio'];
            $horaFin    = $horario['fin'];
        }

        $duracionSlot = $negocio->duracion_turno_min ?? 30;

        // Generar todos los slots del día según el horario del negocio
        $slots = [];
        $cursor = Carbon::parse("{$fecha} {$horaInicio}");
        $fin    = Carbon::parse("{$fecha} {$horaFin}");
        while ($cursor->copy()->addMinutes($duracionSlot)->lte($fin)) {
            $slots[] = $cursor->format('H:i');
            $cursor->addMinutes($duracionSlot);
        }

        // Obtener horas ocupadas (citas activas del profesional ese día)
        $ocupados = Cita::where('profesional_id', $profesionalId)
            ->where('fecha', $fecha)
            ->whereIn('estado', ['pendiente', 'confirmada', 'en_curso'])
            ->pluck('hora_inicio')
            ->map(fn($h) => substr($h, 0, 5))
            ->toArray();

        // Filtrar slots disponibles
        $disponibles = array_values(array_diff($slots, $ocupados));

        // Si es hoy, quitar los slots que ya pasaron
        if ($fecha === now()->toDateString()) {
            $ahora       = now()->format('H:i');
            $disponibles = array_values(array_filter($disponibles, fn($h) => $h > $ahora));
        }

        return response()->json([
            'success'       => true,
            'fecha'         => $fecha,
            'profesional'   => $profesional->nombre_completo,
            'duracion_slot' => $duracionSlot,
            'disponibles'   => $disponibles,
        ]);
    }

    /**
     * POST /api/public/{slug}/reservar
     * Crea una cita pública sin autenticación.
     * Rate limit: 5 intentos por minuto por IP (definido en rutas).
     */
    public function store(Request $request, Negocio $negocio): JsonResponse
    {
        if (!$negocio->activo || !$negocio->booking_activo) {
            return response()->json([
                'success' => false,
                'message' => 'Las reservas online no están disponibles para este negocio.',
            ], 403);
        }

        $validated = $request->validate([
            'servicio_id'    => 'required|integer',
            'profesional_id' => 'required|exists:profesionales,id',
            'fecha'          => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora'           => 'required|date_format:H:i',
            'cliente_nombre' => 'required|string|max:100',
            'cliente_apellido'=> 'sometimes|nullable|string|max:100',
            'cliente_telefono'=> 'required|string|min:7|max:20',
            'cliente_email'  => 'sometimes|nullable|email|max:150',
            'notas_cliente'  => 'sometimes|nullable|string|max:500',
        ], [
            'cliente_nombre.required'   => 'Por favor ingresa tu nombre.',
            'cliente_telefono.required' => 'Por favor ingresa tu número de teléfono.',
            'fecha.after_or_equal'      => 'No puedes reservar en una fecha pasada.',
        ]);

        // Verificar que el servicio pertenece a ESTE negocio
        $servicio = $negocio->servicios()
            ->where('id', $validated['servicio_id'])
            ->where('activo', true)
            ->first();

        if (!$servicio) {
            return response()->json([
                'success' => false,
                'message' => 'El servicio seleccionado no está disponible.',
            ], 422);
        }

        // Verificar que el profesional pertenece a ESTE negocio
        $profesional = Profesional::where('id', $validated['profesional_id'])
            ->where('negocio_id', $negocio->id)
            ->where('activo', true)
            ->first();

        if (!$profesional) {
            return response()->json([
                'success' => false,
                'message' => 'El profesional seleccionado no está disponible.',
            ], 422);
        }

        // Verificar que el slot está disponible (prevención de doble booking)
        $horaInicio = $validated['hora'] . ':00';
        $slotOcupado = Cita::where('profesional_id', $profesional->id)
            ->where('fecha', $validated['fecha'])
            ->where('hora_inicio', $horaInicio)
            ->whereIn('estado', ['pendiente', 'confirmada', 'en_curso'])
            ->exists();

        if ($slotOcupado) {
            return response()->json([
                'success' => false,
                'message' => 'Lo sentimos, ese horario ya fue reservado. Por favor elige otro.',
            ], 409);
        }

        try {
            $cita = DB::transaction(function () use ($validated, $negocio, $servicio, $profesional, $horaInicio) {
                // Buscar o crear el cliente por su teléfono
                $cliente = Cliente::firstOrCreate(
                    ['telefono' => $validated['cliente_telefono']],
                    [
                        'nombre'   => $validated['cliente_nombre'],
                        'apellido' => $validated['cliente_apellido'] ?? null,
                        'email'    => $validated['cliente_email'] ?? null,
                        'activo'   => true,
                    ]
                );

                // Vincular el cliente a este negocio en la tabla pivote (si no existe)
                $negocio->clientes()->syncWithoutDetaching([$cliente->id => [
                    'activo' => true,
                ]]);

                $horaFin = Carbon::parse($horaInicio)
                    ->addMinutes($servicio->duracion_min)
                    ->format('H:i:s');

                // Crear la cita
                $cita = Cita::create([
                    'negocio_id'     => $negocio->id,
                    'cliente_id'     => $cliente->id,
                    'profesional_id' => $profesional->id,
                    'servicio_id'    => $servicio->id,
                    'fecha'          => $validated['fecha'],
                    'hora_inicio'    => $horaInicio,
                    'hora_fin'       => $horaFin,
                    'duracion_min'   => $servicio->duracion_min,
                    'estado'         => 'confirmada',
                    'precio_total'   => $servicio->precio,
                    'moneda'         => $servicio->moneda ?? 'EUR',
                    'canal'          => 'web',
                    'notas_cliente'  => $validated['notas_cliente'] ?? null,
                ]);

                return $cita;
            });

            // Disparar evento CitaCreada → Telegram/Email automático al profesional
            event(new \App\Events\CitaCreada($cita->load(['cliente', 'servicio', 'profesional'])));

            return response()->json([
                'success' => true,
                'message' => '¡Cita reservada con éxito! Te esperamos.',
                'cita'    => [
                    'codigo_referencia' => $cita->codigo_referencia,
                    'fecha'             => $cita->fecha->format('d/m/Y'),
                    'hora'              => substr($cita->hora_inicio, 0, 5),
                    'servicio'          => $servicio->nombre,
                    'profesional'       => $profesional->nombre_completo,
                    'negocio'           => $negocio->nombre,
                    'duracion_min'      => $servicio->duracion_min,
                    'precio'            => $servicio->precio,
                    'moneda'            => $servicio->moneda ?? 'EUR',
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar tu reserva. Por favor inténtalo de nuevo.',
            ], 500);
        }
    }
}
