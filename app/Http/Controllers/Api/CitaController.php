<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Profesional;
use App\Models\Servicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CitaController extends Controller
{
    /**
     * Listar las citas del profesional autenticado.
     * Filtros: ?fecha=2026-07-01  ?estado=pendiente  ?cliente_id=5
     */
    public function index(Request $request): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Solo profesionales pueden acceder.'], 403);
        }

        $query = Cita::with(['cliente', 'servicio'])
            ->where('profesional_id', $profesional->id);

        // Filtros opcionales
        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        // Por defecto, ordena por fecha y hora
        $citas = $query->orderBy('fecha', 'desc')
            ->orderBy('hora_inicio')
            ->paginate(20);

        return response()->json($citas);
    }

    /**
     * Crear una cita manualmente (reservas telefónicas, presenciales, etc.)
     */
    public function store(Request $request): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Solo profesionales pueden crear citas.'], 403);
        }

        $validated = $request->validate([
            'servicio_id'    => 'required|exists:servicios,id',
            'fecha'          => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora'           => 'required|date_format:H:i',
            'canal'          => 'sometimes|in:web,app,whatsapp,telefono,presencial',
            'notas_profesional' => 'sometimes|nullable|string|max:500',
            // Cliente: buscar por id existente o crear por teléfono
            'cliente_id'     => 'required_without:cliente|exists:clientes,id',
            'cliente.nombre'   => 'required_without:cliente_id|string|max:100',
            'cliente.apellido' => 'sometimes|nullable|string|max:100',
            'cliente.telefono' => 'required_without:cliente_id|string|max:20',
        ]);

        $servicio = Servicio::findOrFail($validated['servicio_id']);

        // Obtener o crear el cliente
        if (!empty($validated['cliente_id'])) {
            $cliente = Cliente::findOrFail($validated['cliente_id']);
        } else {
            $cliente = Cliente::firstOrCreate(
                ['telefono' => $validated['cliente']['telefono']],
                [
                    'nombre'   => $validated['cliente']['nombre'],
                    'apellido' => $validated['cliente']['apellido'] ?? null,
                ]
            );
        }

        $horaInicio = $validated['hora'] . ':00';
        $horaFin = Carbon::parse($horaInicio)->addMinutes($servicio->duracion_minutos)->format('H:i:s');

        $cita = Cita::create([
            'negocio_id'       => $profesional->negocio_id,
            'profesional_id'   => $profesional->id,
            'cliente_id'       => $cliente->id,
            'servicio_id'      => $servicio->id,
            'fecha'            => $validated['fecha'],
            'hora_inicio'      => $horaInicio,
            'hora_fin'         => $horaFin,
            'duracion_min'     => $servicio->duracion_min,
            'estado'           => 'confirmada',
            'precio_total'     => $servicio->precio,
            'canal'            => $validated['canal'] ?? 'telefono',
            'notas_profesional'=> $validated['notas_profesional'] ?? null,
        ]);

        event(new \App\Events\CitaCreada($cita->load(['cliente', 'servicio', 'profesional'])));

        return response()->json([
            'success' => true,
            'message' => 'Cita creada correctamente.',
            'cita'    => $cita->load(['cliente', 'servicio'])
        ], 201);
    }

    /**
     * Actualizar una cita: reagendar, cambiar estado o añadir notas.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Solo profesionales pueden modificar citas.'], 403);
        }

        $cita = Cita::where('profesional_id', $profesional->id)->findOrFail($id);

        $validated = $request->validate([
            'fecha'            => 'sometimes|date_format:Y-m-d',
            'hora'             => 'sometimes|date_format:H:i',
            'estado'           => 'sometimes|in:pendiente,confirmada,en_curso,completada,cancelada,no_asistio,rechazada',
            'notas_profesional'=> 'sometimes|nullable|string|max:500',
        ]);

        DB::transaction(function () use (&$cita, $validated) {
            // Si se modifica hora, recalcular hora_fin
            if (!empty($validated['hora'])) {
                $horaInicio = $validated['hora'] . ':00';
                $validated['hora_inicio'] = $horaInicio;
                $validated['hora_fin'] = Carbon::parse($horaInicio)
                    ->addMinutes($cita->duracion_min)
                    ->format('H:i:s');
                unset($validated['hora']);
            }

            $cita->update($validated);
        });

        // Si se cancela, disparar evento
        if (($validated['estado'] ?? null) === 'cancelada') {
            event(new \App\Events\CitaCancelada($cita->fresh()->load(['cliente', 'profesional'])));
        }
        
        if (isset($validated['estado']) && in_array($validated['estado'], ['confirmada', 'completada'])) {
            event(new \App\Events\CitaActualizada($cita->fresh()->load(['cliente', 'profesional'])));
        }

        return response()->json([
            'success' => true,
            'message' => 'Cita actualizada correctamente.',
            'cita'    => $cita->fresh()->load(['cliente', 'servicio'])
        ]);
    }

    /**
     * Cancelar/eliminar una cita (soft delete).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Solo profesionales pueden cancelar citas.'], 403);
        }

        $cita = Cita::where('profesional_id', $profesional->id)->findOrFail($id);

        // Marcar como cancelada antes de eliminar
        $cita->update(['estado' => 'cancelada']);
        $cita->delete();

        event(new \App\Events\CitaCancelada($cita->load(['cliente', 'profesional'])));

        return response()->json([
            'success' => true,
            'message' => 'Cita cancelada y eliminada.'
        ]);
    }

    /**
     * Resolución del profesional autenticado (desde Cliente o Profesional model).
     */
    private function getProfesional(Request $request): ?Profesional
    {
        $user = $request->user();
        if ($user instanceof Profesional) {
            return $user;
        }

        // MODO DEMO: Si el usuario es el Super Admin (modelo User), le damos el primer profesional
        if ($user instanceof \App\Models\User) {
            return Profesional::first();
        }

        return null;
    }
}
