<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Profesional;

class SuscripcionActiva
{
    /**
     * Bloquea el acceso a rutas premium si el plan del negocio está vencido o es free.
     */
    public function handle(Request $request, Closure $next, string $planMinimo = 'basic'): Response
    {
        $user = $request->user();

        // Solo aplica a profesionales
        if (!($user instanceof Profesional)) {
            return $next($request);
        }

        $negocio = $user->negocio;

        if (!$negocio) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el negocio asociado.',
            ], 404);
        }

        $planesOrdenados = ['free', 'basic', 'pro', 'enterprise'];
        $nivelActual  = array_search($negocio->plan, $planesOrdenados);
        $nivelMinimo  = array_search($planMinimo, $planesOrdenados);

        // Verificar que el nivel sea suficiente y que el plan no esté vencido
        if ($nivelActual === false || $nivelActual < $nivelMinimo || !$negocio->planVigente()) {
            return response()->json([
                'success'    => false,
                'codigo'     => 'PLAN_INSUFICIENTE',
                'message'    => "Esta funcionalidad requiere el plan '{$planMinimo}' o superior. Tu plan actual es '{$negocio->plan}'.",
                'plan_actual' => $negocio->plan,
                'plan_minimo' => $planMinimo,
                'upgrade_url' => '/api/suscripciones/planes',
            ], 402); // 402 Payment Required
        }

        return $next($request);
    }
}
