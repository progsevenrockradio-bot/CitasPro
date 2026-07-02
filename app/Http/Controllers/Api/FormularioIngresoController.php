<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\FormularioIngreso;
use App\Models\Profesional;
use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FormularioIngresoController extends Controller
{
    /**
     * Guarda o actualiza el formulario de ingreso (intake form) del paciente.
     */
    public function storeOrUpdate(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'cliente_id'              => 'required|exists:clientes,id',
            'antecedentes_medicos'    => 'nullable|string',
            'antecedentes_familiares' => 'nullable|string',
            'alergias'                => 'nullable|string',
            'medicacion_actual'       => 'nullable|string',
            'tipo_sangre'             => 'nullable|string|max:5',
            'firmado_consentimiento'  => 'required|boolean',
        ]);

        $clienteId = $validated['cliente_id'];

        // ─── Control de Autorización ─────────────────────────────────────────
        if ($user instanceof Cliente) {
            // El cliente solo puede editar su propio formulario
            if ($user->id !== (int) $clienteId) {
                return response()->json(['message' => 'No autorizado para editar este formulario.'], 403);
            }
        } elseif ($user instanceof Profesional) {
            // Un profesional (médico/dueño) solo puede editar si el cliente tiene cita en su negocio
            $tieneCita = Cita::where('cliente_id', $clienteId)
                ->where('negocio_id', $user->negocio_id)
                ->exists();

            if (!$tieneCita) {
                return response()->json(['message' => 'No autorizado. El paciente no pertenece a tu consultorio.'], 403);
            }
        } else {
            return response()->json(['message' => 'Tipo de usuario no autorizado.'], 403);
        }

        // Crear o actualizar el registro
        $formulario = FormularioIngreso::updateOrCreate(
            ['cliente_id' => $clienteId],
            [
                'antecedentes_medicos'    => $validated['antecedentes_medicos'] ?? null,
                'antecedentes_familiares' => $validated['antecedentes_familiares'] ?? null,
                'alergias'                => $validated['alergias'] ?? null,
                'medicacion_actual'       => $validated['medicacion_actual'] ?? null,
                'tipo_sangre'             => $validated['tipo_sangre'] ?? null,
                'firmado_consentimiento'  => $validated['firmado_consentimiento'],
            ]
        );

        Log::info("FormularioIngresoController: Formulario registrado/actualizado para paciente #{$clienteId} por el usuario #{$user->id}");

        return response()->json([
            'success'    => true,
            'message'    => 'Formulario de ingreso guardado con éxito.',
            'formulario' => $formulario
        ]);
    }

    /**
     * Muestra el formulario de ingreso de un paciente.
     */
    public function show(Request $request, $clienteId): JsonResponse
    {
        $user = $request->user();

        // ─── Control de Autorización ─────────────────────────────────────────
        if ($user instanceof Cliente) {
            if ($user->id !== (int) $clienteId) {
                return response()->json(['message' => 'No autorizado para ver este formulario.'], 403);
            }
        } elseif ($user instanceof Profesional) {
            // Verificar que el paciente tenga cita con el negocio del profesional
            $tieneCita = Cita::where('cliente_id', $clienteId)
                ->where('negocio_id', $user->negocio_id)
                ->exists();

            if (!$tieneCita) {
                return response()->json(['message' => 'No autorizado. El paciente no pertenece a tu consultorio.'], 403);
            }
        } else {
            return response()->json(['message' => 'Tipo de usuario no autorizado.'], 403);
        }

        $formulario = FormularioIngreso::where('cliente_id', $clienteId)->first();

        if (!$formulario) {
            return response()->json([
                'success' => false,
                'message' => 'El paciente aún no ha rellenado el formulario de ingreso.'
            ], 404);
        }

        return response()->json([
            'success'    => true,
            'formulario' => $formulario
        ]);
    }
}
