<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppQrService;
use App\Models\Negocio;
use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WhatsAppQrController extends Controller
{
    public function __construct(private readonly WhatsAppQrService $qrService) {}

    /**
     * Conecta el WhatsApp del negocio y devuelve la URL del código QR (Base64).
     */
    public function conectar(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof Profesional) {
            return response()->json(['message' => 'No autorizado. Solo profesionales pueden administrar WhatsApp.'], 403);
        }

        $negocio = $user->negocio;
        if (!$negocio) {
            return response()->json(['message' => 'El profesional no tiene un negocio asociado.'], 422);
        }

        $resultado = $this->qrService->conectarInstancia($negocio);

        if ($resultado) {
            return response()->json($resultado);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo conectar con el gateway de WhatsApp QR.'
        ], 500);
    }

    /**
     * Webhook público que recibe el estado de conexión del dispositivo desde Evolution API.
     */
    public function webhookEstado(Request $request, int $negocioId): JsonResponse
    {
        Log::info("WhatsAppQrController: Recibida notificación de estado para negocio #{$negocioId}", $request->all());

        $negocio = Negocio::findOrFail($negocioId);
        
        // El estado de conexión de la instancia
        $status = $request->input('status'); // 'connected', 'disconnected', etc.

        if ($status === 'connected') {
            $negocio->update(['whatsapp_qr_status' => 'conectado']);
        } elseif ($status === 'disconnected') {
            $negocio->update(['whatsapp_qr_status' => 'desconectado']);
        }

        return response()->json(['status' => 'success']);
    }
}
