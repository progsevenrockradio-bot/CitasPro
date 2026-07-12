<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Cliente;

class CheckGdprConsent
{
    /**
     * Handle an incoming request.
     *
     * Verifica que el cliente involucrado en la petición haya consentido el tratamiento
     * de datos de salud (RGPD) antes de permitir la creación de registros médicos.
     *
     * @param  Request  $request
     * @param  \Closure(Request): (Response)  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // En una API, el cliente podría ser el usuario autenticado (si es la app del paciente)
        // o podría ser especificado en el cuerpo de la petición (si es la app del negocio/recepcionista).
        $clienteId = $request->input('cliente_id') ?? ($request->user() ? $request->user()->id : null);

        if (!$clienteId) {
            return response()->json([
                'error' => 'CONSENT_REQUIRED',
                'message' => 'No se pudo identificar al cliente para validar el consentimiento RGPD.'
            ], 403);
        }

        $cliente = Cliente::find($clienteId);

        if (!$cliente || !$cliente->hasConsent('tratamiento_datos_salud')) {
            return response()->json([
                'error' => 'CONSENT_REQUIRED',
                'message' => 'El cliente no ha otorgado el consentimiento explícito para el tratamiento de datos de salud.'
            ], 403);
        }

        return $next($request);
    }
}
