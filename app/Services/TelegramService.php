<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Profesional;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * TelegramService — Notificaciones al staff via Telegram Bot API.
 *
 * Configuración en .env:
 *   TELEGRAM_BOT_TOKEN=xxxx:yyyy
 *   TELEGRAM_SIMULAR=true   (solo log en desarrollo)
 *
 * Cada Profesional debe tener su telegram_chat_id guardado en su perfil.
 * Para obtenerlo: el profesional envía /start al bot y el sistema
 * lo captura via webhook (implementar en Fase 5).
 *
 * Soporta mensajes con formato HTML y Markdown V2.
 */
class TelegramService
{
    private Client $http;
    private bool   $simular;
    private ?string $botToken;
    private string $apiUrl;

    // Iconos para tipos de eventos
    private const ICONOS = [
        'nueva_cita'    => '📅',
        'modificacion'  => '✏️',
        'cancelacion'   => '❌',
        'recordatorio'  => '⏰',
        'pago'          => '💳',
        'confirmacion'  => '✅',
    ];

    public function __construct()
    {
        $this->simular   = filter_var(config('services.telegram.simular', true), FILTER_VALIDATE_BOOLEAN);
        $this->botToken  = config('services.telegram.bot_token', '');
        $this->apiUrl    = config('services.telegram.api_url', 'https://api.telegram.org/bot');
        $this->http      = new Client(['timeout' => 10, 'connect_timeout' => 5]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Notificaciones específicas de citas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Notifica al profesional sobre una NUEVA cita asignada.
     * Se dispara cuando el cliente reserva una cita.
     */
    public function notificarNuevaCita(Cita $cita): bool
    {
        $profesional = $cita->profesional;

        if (!$this->tieneChatId($profesional)) {
            return false;
        }

        $icono        = self::ICONOS['nueva_cita'];
        $negocio      = $cita->negocio;
        $fechaFormato = \Carbon\Carbon::parse($cita->fecha)->translatedFormat('l d \de F \de Y');
        $horaInicio   = $this->formatearHora($cita->hora_inicio);
        $horaFin      = $this->formatearHora($cita->hora_fin);
        $notas        = $this->generarNotasBloque($cita->notas_cliente);

        $mensaje = <<<MSG
        {$icono} <b>NUEVA CITA RECIBIDA</b>

        📋 <b>Referencia:</b> <code>{$cita->codigo_referencia}</code>
        👤 <b>Cliente:</b> {$cita->cliente->nombre_completo}
        📞 <b>Teléfono:</b> <code>{$cita->cliente->telefono}</code>
        💼 <b>Servicio:</b> {$cita->servicio->nombre}
        📅 <b>Fecha:</b> {$fechaFormato}
        🕐 <b>Hora:</b> {$horaInicio} – {$horaFin}
        ⏱️ <b>Duración:</b> {$cita->duracion_min} min
        💰 <b>Precio:</b> {$cita->precio_total} {$cita->moneda}
        🏪 <b>Negocio:</b> {$negocio->nombre}

        {$notas}
        MSG;

        $botones = $this->generarBotonesAccion($cita);

        return $this->enviarMensaje(
            chatId:  $profesional->telegram_chat_id,
            mensaje: $mensaje,
            botones: $botones,
        );
    }

    /**
     * Notifica al profesional sobre una MODIFICACIÓN en su cita.
     */
    public function notificarModificacionCita(Cita $cita, array $cambios = []): bool
    {
        $profesional = $cita->profesional;

        if (!$this->tieneChatId($profesional)) {
            return false;
        }

        $icono        = self::ICONOS['modificacion'];
        $fechaFormato = \Carbon\Carbon::parse($cita->fecha)->translatedFormat('l d \de F \de Y');
        $horaInicio   = $this->formatearHora($cita->hora_inicio);
        $horaFin      = $this->formatearHora($cita->hora_fin);

        $listaCambios = '';
        foreach ($cambios as $campo => $valor) {
            $listaCambios .= "  • <b>{$campo}:</b> {$valor}\n";
        }

        $mensaje = <<<MSG
        {$icono} <b>CITA MODIFICADA</b>

        📋 <b>Referencia:</b> <code>{$cita->codigo_referencia}</code>
        👤 <b>Cliente:</b> {$cita->cliente->nombre_completo}
        💼 <b>Servicio:</b> {$cita->servicio->nombre}
        📅 <b>Nueva fecha:</b> {$fechaFormato}
        🕐 <b>Nueva hora:</b> {$horaInicio} – {$horaFin}

        {$listaCambios}
        MSG;

        return $this->enviarMensaje(
            chatId:  $profesional->telegram_chat_id,
            mensaje: $mensaje,
        );
    }

    /**
     * Notifica al profesional sobre una CANCELACIÓN de su cita.
     */
    public function notificarCancelacionCita(Cita $cita, string $motivo = ''): bool
    {
        $profesional = $cita->profesional;

        if (!$this->tieneChatId($profesional)) {
            return false;
        }

        $icono        = self::ICONOS['cancelacion'];
        $motivoTexto  = $motivo ?: 'No especificado';
        $fechaFormato = \Carbon\Carbon::parse($cita->fecha)->translatedFormat('l d \de F \de Y');
        $horaInicio   = $this->formatearHora($cita->hora_inicio);

        $mensaje = <<<MSG
        {$icono} <b>CITA CANCELADA</b>

        📋 <b>Referencia:</b> <code>{$cita->codigo_referencia}</code>
        👤 <b>Cliente:</b> {$cita->cliente->nombre_completo}
        📞 <b>Teléfono:</b> <code>{$cita->cliente->telefono}</code>
        💼 <b>Servicio:</b> {$cita->servicio->nombre}
        📅 <b>Fecha afectada:</b> {$fechaFormato} a las {$horaInicio}
        💬 <b>Motivo:</b> {$motivoTexto}

        ⚠️ <i>Este horario ha quedado libre.</i>
        MSG;

        return $this->enviarMensaje(
            chatId:  $profesional->telegram_chat_id,
            mensaje: $mensaje,
        );
    }

    /**
     * Envía recordatorio de próximas citas al profesional (resumen diario).
     */
    public function enviarResumenDiario(Profesional $profesional, array $citas): bool
    {
        if (!$this->tieneChatId($profesional) || empty($citas)) {
            return false;
        }

        $total   = count($citas);
        $icono   = self::ICONOS['recordatorio'];
        $lineas  = '';

        foreach ($citas as $cita) {
            $lineas .= "  🕐 <b>{$this->formatearHora($cita->hora_inicio)}</b> – {$cita->cliente->nombre_completo} ({$cita->servicio->nombre})\n";
        }

        $mensaje = <<<MSG
        {$icono} <b>TUS CITAS DE HOY</b>

        👋 Buenos días, <b>{$profesional->nombre}</b>
        📅 Tienes <b>{$total} cita(s)</b> programada(s):

        {$lineas}
        Recuerda confirmarlas antes de que llegue el cliente. ¡Buen día!
        MSG;

        return $this->enviarMensaje(
            chatId:  $profesional->telegram_chat_id,
            mensaje: $mensaje,
        );
    }

    /**
     * Envía un mensaje de texto libre a un chat_id de Telegram.
     */
    public function enviarTextoLibre(string|int $chatId, string $mensaje): bool
    {
        return $this->enviarMensaje(chatId: (string) $chatId, mensaje: $mensaje);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Método central de envío
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía un mensaje via Telegram Bot API.
     *
     * @param  string       $chatId   Chat ID del destinatario
     * @param  string       $mensaje  Texto con HTML o MarkdownV2
     * @param  array|null   $botones  Inline keyboard opcional
     * @param  string       $modo     'HTML' | 'MarkdownV2' | ''
     */
    public function enviarMensaje(
        string $chatId,
        string $mensaje,
        ?array $botones = null,
        string $modo = 'HTML'
    ): bool {
        // Limpiar indentación del heredoc
        $mensaje = $this->limpiarMensaje($mensaje);

        // ── Modo simulación ──────────────────────────────────────────────────
        if ($this->simular) {
            Log::channel('stack')->info("🤖 [Telegram SIMULADO] → Chat: {$chatId}", [
                'mensaje' => strip_tags($mensaje),
                'botones' => $botones ? count($botones) . ' botones' : 'ninguno',
            ]);
            return true;
        }

        // ── Modo producción ──────────────────────────────────────────────────
        if (empty($this->botToken)) {
            Log::error('TelegramService: TELEGRAM_BOT_TOKEN no configurado en .env');
            return false;
        }

        try {
            $payload = [
                'chat_id'                  => $chatId,
                'text'                     => $mensaje,
                'parse_mode'               => $modo,
                'disable_web_page_preview' => true,
            ];

            if ($botones) {
                $payload['reply_markup'] = [
                    'inline_keyboard' => $botones,
                ];
            }

            $endpoint = "{$this->apiUrl}{$this->botToken}/sendMessage";

            $response = $this->http->post($endpoint, ['json' => $payload]);
            $body     = json_decode($response->getBody()->getContents(), true);

            if (!($body['ok'] ?? false)) {
                Log::error("Telegram API error → Chat: {$chatId}", ['response' => $body]);
                return false;
            }

            Log::info("Telegram enviado OK → Chat: {$chatId}", [
                'message_id' => $body['result']['message_id'] ?? null,
            ]);

            return true;

        } catch (RequestException $e) {
            Log::error("Telegram API exception → Chat: {$chatId}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    private function tieneChatId(Profesional $profesional): bool
    {
        if (empty($profesional->telegram_chat_id)) {
            Log::warning("TelegramService: Profesional #{$profesional->id} ({$profesional->nombre}) no tiene telegram_chat_id configurado.");
            return false;
        }
        return true;
    }

    private function formatearHora(string $hora): string
    {
        return substr($hora, 0, 5); // HH:MM
    }

    private function generarNotasBloque(?string $notas): string
    {
        if (empty($notas)) {
            return '';
        }
        return "\n💬 <b>Notas del cliente:</b>\n<i>{$notas}</i>";
    }

    /**
     * Genera botones inline de acción rápida para una cita.
     * Estos callbacks deben manejarse en el webhook del bot (Fase 5).
     */
    private function generarBotonesAccion(Cita $cita): array
    {
        return [
            [
                ['text' => '✅ Confirmar', 'callback_data' => "confirmar_cita:{$cita->id}"],
                ['text' => '❌ Rechazar',  'callback_data' => "rechazar_cita:{$cita->id}"],
            ],
        ];
    }

    /**
     * Limpia la indentación del heredoc y elimina espacios extra.
     */
    private function limpiarMensaje(string $mensaje): string
    {
        $lineas = explode("\n", $mensaje);
        $lineas = array_map(fn ($l) => ltrim($l), $lineas);
        return implode("\n", $lineas);
    }

    /**
     * Responde a una Callback Query de Telegram para quitar el estado de carga del botón.
     */
    public function responderCallbackQuery(string $callbackQueryId, string $texto = ''): bool
    {
        if ($this->simular) {
            Log::channel('stack')->info("🤖 [Telegram SIMULADO] → Responder Callback: {$callbackQueryId} con texto: {$texto}");
            return true;
        }

        if (empty($this->botToken)) {
            return false;
        }

        try {
            $endpoint = "{$this->apiUrl}{$this->botToken}/answerCallbackQuery";
            $payload = [
                'callback_query_id' => $callbackQueryId,
                'text'              => $texto,
            ];
            $this->http->post($endpoint, ['json' => $payload]);
            return true;
        } catch (\Exception $e) {
            Log::error("Telegram API responderCallbackQuery exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Edita el texto de un mensaje existente en Telegram.
     */
    public function editarMensaje(string|int $chatId, int $messageId, string $nuevoTexto, string $modo = 'HTML'): bool
    {
        $nuevoTexto = $this->limpiarMensaje($nuevoTexto);

        if ($this->simular) {
            Log::channel('stack')->info("🤖 [Telegram SIMULADO] → Editar Mensaje en Chat {$chatId}, Msg ID {$messageId}", [
                'texto' => strip_tags($nuevoTexto)
            ]);
            return true;
        }

        if (empty($this->botToken)) {
            return false;
        }

        try {
            $endpoint = "{$this->apiUrl}{$this->botToken}/editMessageText";
            $payload = [
                'chat_id'    => $chatId,
                'message_id' => $messageId,
                'text'       => $nuevoTexto,
                'parse_mode' => $modo,
            ];
            $this->http->post($endpoint, ['json' => $payload]);
            return true;
        } catch (\Exception $e) {
            Log::error("Telegram API editMessageText exception: " . $e->getMessage());
            return false;
        }
    }
}
