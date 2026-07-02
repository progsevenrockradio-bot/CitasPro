<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FichaClinica;
use App\Models\PacienteAcceso;
use App\Models\Profesional;
use App\Models\Cliente;
use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FichaClinicaController extends Controller
{
    /**
     * Crea una nueva nota/ficha clínica para una consulta médica.
     * Solo doctores autorizados (que atienden la cita).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof Profesional) {
            return response()->json(['message' => 'Solo profesionales de salud pueden crear fichas clínicas.'], 403);
        }

        $validated = $request->validate([
            'cliente_id'      => 'required|exists:clientes,id',
            'cita_id'         => 'nullable|exists:citas,id',
            'motivo_consulta' => 'required|string',
            'diagnostico'     => 'required|string',
            'tratamiento'     => 'nullable|string',
            'receta'          => 'nullable|string',
            'notas'           => 'nullable|string',
        ]);

        $clienteId = $validated['cliente_id'];
        $citaId = $validated['cita_id'] ?? null;

        // Validar que la cita pertenezca al paciente y al negocio del profesional
        if ($citaId) {
            $cita = Cita::findOrFail($citaId);
            if ($cita->cliente_id !== (int) $clienteId || $cita->negocio_id !== $user->negocio_id) {
                return response()->json(['message' => 'Cita no coincide con el paciente o consultorio.'], 422);
            }
        }

        // Crear la ficha clínica
        $ficha = FichaClinica::create([
            'cliente_id'      => $clienteId,
            'profesional_id'  => $user->id,
            'cita_id'         => $citaId,
            'motivo_consulta' => $validated['motivo_consulta'],
            'diagnostico'     => $validated['diagnostico'],
            'tratamiento'     => $validated['tratamiento'] ?? null,
            'receta'          => $validated['receta'] ?? null,
            'notas'           => $validated['notas'] ?? null,
        ]);

        Log::info("FichaClinicaController: Ficha clínica #{$ficha->id} creada por doctor #{$user->id} para paciente #{$clienteId}");

        return response()->json([
            'success' => true,
            'message' => 'Ficha clínica guardada correctamente en el expediente del paciente.',
            'ficha'   => $ficha
        ], 210);
    }

    /**
     * Muestra todo el historial clínico (fichas clínicas) de un paciente.
     * Aplica la segregación y compartición de accesos.
     */
    public function indexPaciente(Request $request, $clienteId): JsonResponse
    {
        $user = $request->user();

        // 1. Validar accesibilidad
        if (!$this->hasAccessToPatient($user, $clienteId)) {
            return response()->json(['message' => 'Acceso denegado. No tienes autorización para ver el historial clínico de este paciente.'], 403);
        }

        // 2. Traer el historial
        $fichas = FichaClinica::where('cliente_id', $clienteId)
            ->with(['profesional:id,nombre,apellido,titulo'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'cliente_id' => $clienteId,
            'historial' => $fichas
        ]);
    }

    /**
     * Visualiza una ficha clínica individual.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $ficha = FichaClinica::with(['profesional:id,nombre,apellido,titulo'])->findOrFail($id);

        // Validar accesibilidad
        if (!$this->hasAccessToPatient($user, $ficha->cliente_id)) {
            return response()->json(['message' => 'Acceso denegado. No tienes autorización para ver esta ficha clínica.'], 403);
        }

        return response()->json([
            'success' => true,
            'ficha'   => $ficha
        ]);
    }

    /**
     * Comparte el acceso a la historia clínica de un paciente con otro colega médico.
     */
    public function compartirAcceso(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof Profesional) {
            return response()->json(['message' => 'Solo profesionales médicos pueden compartir accesos.'], 403);
        }

        $validated = $request->validate([
            'cliente_id'     => 'required|exists:clientes,id',
            'profesional_id' => 'required|exists:profesionales,id', // El doctor a quien se comparte
        ]);

        $clienteId = $validated['cliente_id'];
        $profesionalId = $validated['profesional_id'];

        // Solo permitir compartir si el profesional que concede tiene acceso al paciente
        if (!$this->hasAccessToPatient($user, $clienteId)) {
            return response()->json(['message' => 'No puedes compartir el acceso de un paciente a quien no atiendes.'], 403);
        }

        // Crear la relación de acceso compartido
        $acceso = PacienteAcceso::firstOrCreate([
            'cliente_id'     => $clienteId,
            'profesional_id' => $profesionalId,
        ], [
            'concedido_por'  => $user->id,
        ]);

        Log::info("FichaClinicaController: Doctor #{$user->id} compartió historial de paciente #{$clienteId} con el doctor #{$profesionalId}");

        return response()->json([
            'success' => true,
            'message' => 'Acceso a la historia clínica compartido correctamente con tu colega.',
            'acceso'  => $acceso
        ]);
    }

    /**
     * Validador de permisos de acceso.
     */
    private function hasAccessToPatient($user, $clienteId): bool
    {
        // El paciente mismo siempre tiene acceso
        if ($user instanceof Cliente && $user->id === (int) $clienteId) {
            return true;
        }

        if ($user instanceof Profesional) {
            // El dueño del consultorio/clínica siempre tiene acceso a todos los expedientes de su negocio
            if (in_array($user->rol, ['dueño', 'admin'])) {
                return true;
            }

            // Un médico/dentista tiene acceso si ha atendido citas de este paciente
            $tieneCita = Cita::where('cliente_id', $clienteId)
                ->where('profesional_id', $user->id)
                ->exists();

            if ($tieneCita) {
                return true;
            }

            // O si otro colega le compartió acceso explícitamente a este expediente
            $tieneAccesoCompartido = PacienteAcceso::where('cliente_id', $clienteId)
                ->where('profesional_id', $user->id)
                ->exists();

            if ($tieneAccesoCompartido) {
                return true;
            }
        }

        return false;
    }
}
