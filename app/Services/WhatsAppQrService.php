<?php

namespace App\Services;

use App\Models\Negocio;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppQrService
{
    private ?string $apiUrl;
    private ?string $globalApiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp_qr.api_url');
        $this->globalApiKey = config('services.whatsapp_qr.api_key');
    }

    /**
     * Conecta o genera la instancia del código QR para un negocio.
     * En local/demo, devuelve una simulación Base64.
     */
    public function conectarInstancia(Negocio $negocio): ?array
    {
        $instanceName = 'citaspro_negocio_' . $negocio->id;

        if (empty($this->apiUrl) || $this->apiUrl === 'https://api.tuservidor.com') {
            // Modo simulación local
            $negocio->update([
                'whatsapp_modelo'           => 'qr',
                'whatsapp_session_instance' => $instanceName,
                'whatsapp_session_token'    => 'mock_token_' . uniqid(),
                'whatsapp_qr_status'        => 'escaneando'
            ]);

            Log::info("WhatsAppQrService: Instancia simulada creada para negocio #{$negocio->id}");

            // QR simulado
            $mockQrBase64 = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=CitasProDemoQR_'.uniqid();
            return [
                'success' => true,
                'status'  => 'escaneando',
                'qrcode'  => $mockQrBase64,
                'message' => 'Instancia de WhatsApp QR creada (Modo Simulación).'
            ];
        }

        try {
            // 1. Crear la instancia en Evolution API
            $response = Http::withHeaders(['apikey' => $this->globalApiKey])
                ->post("{$this->apiUrl}/instance/create", [
                    'instanceName' => $instanceName,
                    'token'        => bin2hex(random_bytes(16)),
                    'qrcode'       => true
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $instanceToken = $data['hash']['apikey'] ?? $data['instance']['token'];

                $negocio->update([
                    'whatsapp_modelo'           => 'qr',
                    'whatsapp_session_instance' => $instanceName,
                    'whatsapp_session_token'    => $instanceToken,
                    'whatsapp_qr_status'        => 'escaneando'
                ]);

                // 2. Obtener el código QR de conexión
                $qrResponse = Http::withHeaders(['apikey' => $instanceToken])
                    ->get("{$this->apiUrl}/instance/connect/{$instanceName}");

                if ($qrResponse->successful()) {
                    $qrData = $qrResponse->json();
                    return [
                        'success' => true,
                        'status'  => 'escaneando',
                        'qrcode'  => $qrData['base64'] ?? $qrData['code'] ?? null,
                    ];
                }
            }

            Log::error('WhatsAppQrService: Error al crear instancia: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('WhatsAppQrService: Exception al crear instancia: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Envía un mensaje en texto plano mediante la sesión del negocio.
     */
    public function enviarMensaje(Negocio $negocio, string $numero, string $texto): bool
    {
        $instanceName = $negocio->whatsapp_session_instance;
        $token        = $negocio->whatsapp_session_token;

        // Quitar caracteres no numéricos y el signo +
        $numeroLimpiado = preg_replace('/[^\d]/', '', $numero);

        if (empty($token) || empty($this->apiUrl) || $this->apiUrl === 'https://api.tuservidor.com') {
            Log::info("WhatsApp QR (Simulado - Instancia {$instanceName}) a {$numeroLimpiado}: {$texto}");
            return true;
        }

        try {
            $response = Http::withHeaders(['apikey' => $token])
                ->post("{$this->apiUrl}/message/sendText/{$instanceName}", [
                    'number'      => $numeroLimpiado,
                    'options'     => [
                        'delay'    => 1000,
                        'presence' => 'composing'
                    ],
                    'textMessage' => [
                        'text' => $texto
                    ]
                ]);

            if ($response->successful()) {
                Log::info("WhatsApp QR: Mensaje enviado con éxito a {$numeroLimpiado}");
                return true;
            }

            Log::error("WhatsApp QR Error: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp QR Exception: " . $e->getMessage());
            return false;
        }
    }
}
