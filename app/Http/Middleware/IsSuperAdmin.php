<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class IsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Verificar que el usuario esté autenticado
        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        // Verificar que sea una instancia del modelo User (y no Cliente o Profesional)
        if (!($user instanceof User)) {
            return response()->json(['message' => 'Acceso denegado. Se requieren permisos de Súper Administrador.'], 403);
        }

        // Verificar que el token tenga el permiso 'super-admin'
        if (!$user->tokenCan('super-admin')) {
            return response()->json(['message' => 'Acceso denegado. Permisos insuficientes.'], 403);
        }

        return $next($request);
    }
}
