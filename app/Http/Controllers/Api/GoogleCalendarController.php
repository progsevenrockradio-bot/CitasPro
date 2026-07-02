<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profesional;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    public function __construct(private GoogleCalendarService $googleCalendarService) {}

    /**
     * Genera la URL de Google OAuth y la devuelve al frontend.
     */
    public function redirect(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof Profesional) {
            return response()->json(['message' => 'No autorizado. Solo profesionales pueden conectar su calendario.'], 403);
        }

        try {
            $url = $this->googleCalendarService->generateAuthUrl($user->id);

            return response()->json([
                'success' => true,
                'url'     => $url
            ]);
        } catch (\Exception $e) {
            Log::error("GoogleCalendarController: Error al generar URL de redirect: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar la URL de autorización de Google.'
            ], 500);
        }
    }

    /**
     * Callback que recibe la autorización de Google y la guarda.
     * Esta ruta es pública y maneja la redirección final al dashboard del frontend.
     */
    public function callback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');

        // URL del dashboard frontend
        $frontendUrl = env('APP_URL', 'https://jmfn8n.top') . '/dashboard';

        if (!$code || !$state) {
            Log::warning("GoogleCalendarController: Callback sin código o estado.");
            return redirect($frontendUrl . '?google_error=auth_cancelled');
        }

        try {
            // Desencriptar el ID del profesional desde el parámetro state
            $profesionalId = decrypt($state);
            $profesional = Profesional::findOrFail($profesionalId);

            // Intercambiar código por token y guardarlo
            $this->googleCalendarService->exchangeCodeAndSaveTokens($code, $profesional);

            return redirect($frontendUrl . '?google_success=1');
        } catch (\Exception $e) {
            Log::error("GoogleCalendarController: Error en callback de Google: " . $e->getMessage());
            return redirect($frontendUrl . '?google_error=' . urlencode($e->getMessage()));
        }
    }
}
