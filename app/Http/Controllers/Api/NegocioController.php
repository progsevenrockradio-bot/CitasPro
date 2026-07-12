<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Negocio;
use App\Models\Profesional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        $negocio = Negocio::with(['categoria', 'datosFiscales', 'paisObj'])->findOrFail($profesional->negocio_id);

        return response()->json([
            'success' => true,
            'negocio' => [
                'id'                 => $negocio->id,
                'nombre'             => $negocio->nombre,
                'slug'               => $negocio->slug,
                'logo'               => $negocio->logo ? asset('storage/' . $negocio->logo) : null,
                'descripcion'        => $negocio->descripcion,
                'categoria_id'       => $negocio->categoria_id,
                'es_medico'          => (bool) $negocio->es_medico,
                'telefono'           => $negocio->telefono,
                'email'              => $negocio->email,
                'sitio_web'          => $negocio->sitio_web,
                'direccion'          => $negocio->direccion,
                'ciudad'             => $negocio->ciudad,
                'pais_id'            => $negocio->pais_id,
                'estado_id'          => $negocio->estado_id,
                'ciudad_id'          => $negocio->ciudad_id,
                'pais'               => $negocio->pais,
                'horario_apertura'   => $negocio->horario_apertura,
                'duracion_turno_min' => $negocio->duracion_turno_min,
                'plan'               => $negocio->plan,
                // ── Reserva Pública ──────────────────────────────
                'booking_activo'     => (bool) $negocio->booking_activo,
                'booking_mensaje'    => $negocio->booking_mensaje,
                'booking_url'        => $negocio->public_booking_url,
                'telefonos_adicionales' => $negocio->telefonos_adicionales ?: [],
                'verification_phone_index' => $negocio->verification_phone_index,
                'numero_fiscal'      => $negocio->numero_fiscal,
                'datos_fiscales'     => $negocio->datosFiscales ? $negocio->datosFiscales->datos_fiscales : null,
                'pais_fiscal_fields' => $negocio->paisObj ? $negocio->paisObj->fiscal_fields : null,
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

        // Si viene como string JSON desde FormData, decodificarlo
        if ($request->has('horario_apertura') && is_string($request->input('horario_apertura'))) {
            $request->merge([
                'horario_apertura' => json_decode($request->input('horario_apertura'), true)
            ]);
        }

        if ($request->has('telefonos_adicionales') && is_string($request->input('telefonos_adicionales'))) {
            $request->merge([
                'telefonos_adicionales' => json_decode($request->input('telefonos_adicionales'), true)
            ]);
        }

        $validated = $request->validate([
            'nombre'                    => 'sometimes|string|max:150',
            'logo'                      => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'descripcion'               => 'sometimes|nullable|string|max:500',
            'telefono'                  => 'sometimes|nullable|string|max:20',
            'whatsapp'                  => 'sometimes|nullable|string|max:20',
            'email'                     => 'sometimes|nullable|email|max:100',
            'booking_activo'            => 'sometimes|boolean',
            'booking_mensaje'           => 'sometimes|nullable|string|max:500',
            
            // Payment Integrations
            'mp_access_token'           => 'sometimes|nullable|string',
            'stripe_public_key'         => 'sometimes|nullable|string',
            'stripe_secret_key'         => 'sometimes|nullable|string',
            'mp_public_key'             => 'sometimes|nullable|string',
            'cobro_online_obligatorio'  => 'sometimes|boolean',
            'pasarela_preferida'        => 'sometimes|nullable|string|in:stripe,mercadopago',
            'sitio_web'                 => 'sometimes|nullable|url|max:255',
            'direccion'                 => 'sometimes|nullable|string|max:255',
            'ciudad'                    => 'sometimes|nullable|string|max:100',
            'pais_id'                   => 'sometimes|nullable|integer|exists:paises,id',
            'estado_id'                 => 'sometimes|nullable|integer|exists:estados,id',
            'ciudad_id'                 => 'sometimes|nullable|integer|exists:ciudades,id',
            'pais'                      => 'sometimes|nullable|string|max:100',
            'horario_apertura'          => 'sometimes|nullable|array',
            'duracion_turno_min'        => 'sometimes|integer|min:5|max:480',
            'anticipacion_min_reserva'  => 'sometimes|integer|min:0',
            'cancelacion_limite_horas'  => 'sometimes|integer|min:0',
            'es_medico'                 => 'sometimes|boolean',
            'telefonos_adicionales'     => 'sometimes|nullable|array',
            'telefonos_adicionales.*.number' => 'required_with:telefonos_adicionales|string',
            'telefonos_adicionales.*.type'   => 'in:local,mobile,fax',
            'verification_phone_index'  => 'sometimes|nullable|integer',
            'numero_fiscal'             => 'sometimes|nullable|string|max:50',
        ]);

        $negocio = Negocio::findOrFail($profesional->negocio_id);

        if ($request->hasFile('logo')) {
            if ($negocio->logo) {
                Storage::disk('public')->delete($negocio->logo);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $path;
        }

        $negocio->update($validated);
        
        // Agregar la URL completa para el frontend
        $negocio->logo_url = $negocio->logo ? asset('storage/' . $negocio->logo) : null;

        return response()->json([
            'success' => true,
            'message' => 'Configuración del negocio actualizada correctamente.',
            'negocio' => $negocio
        ]);
    }

    public function updateFiscalData(\App\Http\Requests\StoreNegocioDatosFiscalesRequest $request): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        if (!in_array($profesional->rol, ['dueño', 'admin'])) {
            return response()->json([
                'message' => 'No tienes permisos para modificar el negocio. Solo el dueño o admin puede hacerlo.'
            ], 403);
        }

        $negocio = Negocio::findOrFail($profesional->negocio_id);
        $datos = $request->validated();
        
        $negocio->datosFiscales()->updateOrCreate(
            ['negocio_id' => $negocio->id],
            [
                'pais_id' => $datos['pais_id'],
                'datos_fiscales' => $datos['datos_fiscales'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Datos fiscales actualizados correctamente.',
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
