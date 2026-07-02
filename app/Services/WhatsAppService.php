<?php

namespace App\Services;

use App\Models\Cita;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppService — Integración con WhatsApp Cloud API (Meta).
 *
 * Documentación: https://developers.facebook.com/docs/whatsapp/cloud-api
 *
 * Modo simulación (WHATSAPP_SIMULAR=true en .env):
 *   → Solo registra en log. No hace llamadas reales.
 *
 * Modo producción (WHATSAPP_SIMULAR=false):
 *   → Llama a la Graph API de Meta con plantillas aprobadas.
 */
class WhatsAppService
{
    private Client $http;
    private bool   $simular;
    private ?string $apiUrl;
    private ?string $token;
    private ?string $phoneId;
 
    public function __construct(private readonly WhatsAppQrService $qrService)
    {
        $this->simular = filter_var(config('services.whatsapp.simular', true), FILTER_VALIDATE_BOOLEAN);
        $this->apiUrl  = config('services.whatsapp.api_url');
        $this->token   = config('services.whatsapp.token', '');
        $this->phoneId = config('services.whatsapp.phone_id', '');
 
        $this->http = new Client([
            'timeout'         => 15,
            'connect_timeout' => 5,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos públicos de mensajería
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía recordatorio de cita al cliente (24h o 1h antes).
     *
     * Plantilla esperada en Meta con variables:
     *   {{1}} = nombre del cliente
     *   {{2}} = nombre del servicio
     *   {{3}} = fecha (Lunes 30 de Junio)
     *   {{4}} = hora (14:30)
     *   {{5}} = nombre del negocio
     */
    public function enviarRecordatorio(Cita $cita): bool
    {
        $cliente    = $cita->cliente;
        $servicio   = $cita->servicio;
        $negocio    = $cita->negocio;
        $profesional = $cita->profesional;

        if ($negocio->whatsapp_modelo === 'qr') {
            $fecha = $cita->fecha->translatedFormat('l d \d\e F');
            $hora = substr($cita->hora_inicio, 0, 5);
            $texto = "Hola {$cliente->nombre}, recordatorio de tu cita para {$servicio->nombre} el {$fecha} a las {$hora} con {$profesional->nombre_completo} en {$negocio->nombre}.";
            return $this->qrService->enviarMensaje($negocio, $cliente->telefono, $texto);
        }

        $parametros = [
            $cliente->nombre,
            $servicio->nombre,
            $cita->fecha->translatedFormat('l d \d\e F'),
            substr($cita->hora_inicio, 0, 5),
            $negocio->nombre,
            $profesional->nombre_completo,
        ];

        return $this->enviarPlantilla(
            destinatario: $cliente->telefono,
            plantilla:    config('services.whatsapp.template_recordatorio'),
            parametros:   $parametros,
            contexto:     "Recordatorio cita #{$cita->id} → Cliente: {$cliente->nombre}",
        );
    }

    /**
     * Envía confirmación de nueva cita al cliente.
     *
     * Plantilla con variables:
     *   {{1}} = nombre cliente, {{2}} = servicio, {{3}} = fecha, {{4}} = hora, {{5}} = negocio
     */
    public function enviarConfirmacion(Cita $cita): bool
    {
        $cliente  = $cita->cliente;
        $servicio = $cita->servicio;
        $negocio  = $cita->negocio;

        if ($negocio->whatsapp_modelo === 'qr') {
            $fecha = $cita->fecha->translatedFormat('l d \d\e F');
            $hora = substr($cita->hora_inicio, 0, 5);
            $texto = "Hola {$cliente->nombre}, confirmamos tu cita para {$servicio->nombre} el {$fecha} a las {$hora} en {$negocio->nombre}. Código de reserva: {$cita->codigo_referencia}.";
            return $this->qrService->enviarMensaje($negocio, $cliente->telefono, $texto);
        }

        $parametros = [
            $cliente->nombre,
            $servicio->nombre,
            $cita->fecha->translatedFormat('l d \d\e F'),
            substr($cita->hora_inicio, 0, 5),
            $negocio->nombre,
            $cita->codigo_referencia,
        ];

        return $this->enviarPlantilla(
            destinatario: $cliente->telefono,
            plantilla:    config('services.whatsapp.template_confirmacion'),
            parametros:   $parametros,
            contexto:     "Confirmación cita {$cita->codigo_referencia}",
        );
    }

    /**
     * Envía notificación de cancelación al cliente.
     */
    public function enviarCancelacion(Cita $cita, string $motivoCancelacion = ''): bool
    {
        $cliente  = $cita->cliente;
        $servicio = $cita->servicio;
        $negocio  = $cita->negocio;

        if ($negocio->whatsapp_modelo === 'qr') {
            $fecha = $cita->fecha->translatedFormat('l d \d\e F');
            $hora = substr($cita->hora_inicio, 0, 5);
            $texto = "Hola {$cliente->nombre}, lamentamos informarte que tu cita para {$servicio->nombre} el {$fecha} a las {$hora} ha sido cancelada. Motivo: " . ($motivoCancelacion ?: 'No especificado') . ".";
            return $this->qrService->enviarMensaje($negocio, $cliente->telefono, $texto);
        }

        $parametros = [
            $cliente->nombre,
            $servicio->nombre,
            $cita->fecha->translatedFormat('l d \d\e F'),
            substr($cita->hora_inicio, 0, 5),
            $motivoCancelacion ?: 'No especificado',
        ];

        return $this->enviarPlantilla(
            destinatario: $cliente->telefono,
            plantilla:    config('services.whatsapp.template_cancelacion'),
            parametros:   $parametros,
            contexto:     "Cancelación cita {$cita->codigo_referencia}",
        );
    }

    /**
     * Envía un mensaje de texto libre (solo para números verificados en la misma cuenta,
     * o dentro de la ventana de 24h de conversación activa).
     */
    public function enviarTexto(string $destinatario, string $mensaje): bool
    {
        if ($this->simular) {
            Log::channel('stack')->info("📱 [WhatsApp SIMULADO] Para: {$destinatario}", [
                'mensaje' => $mensaje,
            ]);
            return true;
        }

        try {
            $endpoint = "{$this->apiUrl}/{$this->phoneId}/messages";

            $this->http->post($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $destinatario,
                    'type'              => 'text',
                    'text'              => ['body' => $mensaje],
                ],
            ]);

            return true;

        } catch (RequestException $e) {
            Log::error("WhatsApp API error (texto) → {$destinatario}", [
                'error'    => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Método central: enviar plantilla aprobada por Meta
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía un mensaje usando una plantilla aprobada en Meta Business Manager.
     *
     * @param  string   $destinatario  Número E.164 del destinatario
     * @param  string   $plantilla     Nombre de la plantilla en Meta
     * @param  string[] $parametros    Valores para los parámetros {{1}}, {{2}}, ...
     * @param  string   $contexto      Texto de log para trazabilidad
     * @param  string   $idioma        Código de idioma (es_ES, en_US, etc.)
     */
    public function enviarPlantilla(
        string $destinatario,
        string $plantilla,
        array  $parametros = [],
        string $contexto = '',
        string $idioma = 'es_ES'
    ): bool {
        // ── Modo simulación ──────────────────────────────────────────────────
        if ($this->simular) {
            Log::channel('stack')->info("📲 [WhatsApp SIMULADO] Plantilla: {$plantilla} → {$destinatario}", [
                'contexto'   => $contexto,
                'parametros' => $parametros,
            ]);
            return true;
        }

        // ── Modo producción: llamada real a Meta API ──────────────────────────
        if (empty($this->token) || empty($this->phoneId)) {
            Log::error('WhatsAppService: Token o PhoneID no configurados en .env');
            return false;
        }

        try {
            $components = [];

            if (!empty($parametros)) {
                $components[] = [
                    'type'       => 'body',
                    'parameters' => array_map(
                        fn ($valor) => ['type' => 'text', 'text' => (string) $valor],
                        $parametros
                    ),
                ];
            }

            $endpoint = "{$this->apiUrl}/{$this->phoneId}/messages";

            $response = $this->http->post($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $destinatario,
                    'type'              => 'template',
                    'template'          => [
                        'name'       => $plantilla,
                        'language'   => ['code' => $idioma],
                        'components' => $components,
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body       = json_decode($response->getBody()->getContents(), true);

            Log::info("WhatsApp enviado OK → {$destinatario} | Plantilla: {$plantilla}", [
                'message_id' => $body['messages'][0]['id'] ?? null,
                'status'     => $statusCode,
            ]);

            return true;

        } catch (RequestException $e) {
            $responseBody = $e->hasResponse()
                ? $e->getResponse()->getBody()->getContents()
                : 'Sin respuesta';

            Log::error("WhatsApp API error → {$destinatario} | Plantilla: {$plantilla}", [
                'contexto'  => $contexto,
                'error'     => $e->getMessage(),
                'response'  => $responseBody,
            ]);

            return false;
        }
    }
}
