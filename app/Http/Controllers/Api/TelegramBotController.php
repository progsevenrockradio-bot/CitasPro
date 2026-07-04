<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Profesional;
use App\Services\TelegramService;
use App\Events\CitaActualizada;
use App\Events\CitaCancelada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    public function __construct(
        private readonly TelegramService $telegram
    ) {}

    /**
     * POST /api/telegram/webhook
     * Entrada principal para el webhook del bot de Telegram.
     */
    public function handle(Request $request): JsonResponse
    {
        $update = $request->all();

        Log::info('Telegram Webhook update recibido:', ['update' => $update]);

        // 1. Manejar Callback Queries (Botones de Confirmar/Rechazar)
        if (isset($update['callback_query'])) {
            return $this->handleCallbackQuery($update['callback_query']);
        }

        // 2. Manejar Mensajes directos (ej. Comando /start)
        if (isset($update['message'])) {
            return $this->handleMessage($update['message']);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Maneja mensajes directos enviados al Bot (como /start)
     */
    private function handleMessage(array $message): JsonResponse
    {
        $chatId = $message['chat']['id'] ?? null;
        $text   = trim($message['text'] ?? '');

        if (!$chatId) {
            return response()->json(['success' => true]);
        }

        // Detectar si es el comando /start
        if (str_starts_with($text, '/start')) {
            $parts = explode(' ', $text, 2);
            $arg = isset($parts[1]) ? trim($parts[1]) : '';

            if (empty($arg)) {
                // Instrucciones si no hay parámetros
                $this->telegram->enviarTextoLibre(
                    $chatId,
                    "¡Bienvenido a CitasPro! 📅\n\nPara vincular este chat de Telegram con tu perfil de Profesional y comenzar a recibir alertas de reservas en tiempo real, escribe /start seguido de tu teléfono registrado. Por ejemplo:\n\n<code>/start +34600111222</code>"
                );
                return response()->json(['success' => true]);
            }

            // Normalizar y buscar el profesional
            $telefonoLimpio = $this->normalizarTelefono($arg);
            $profesional = Profesional::where('telefono', $telefonoLimpio)
                ->orWhere('email', $arg)
                ->first();

            if ($profesional) {
                // Vincular
                $profesional->telegram_chat_id = (string) $chatId;
                $profesional->notificaciones_telegram = true;
                $profesional->save();

                $this->telegram->enviarTextoLibre(
                    $chatId,
                    "✅ ¡Perfecto, {$profesional->nombre}! Tu cuenta ha sido vinculada correctamente.\n\nA partir de este momento recibirás notificaciones aquí cuando los clientes reserven o cancelen citas."
                );
            } else {
                // No encontrado
                $this->telegram->enviarTextoLibre(
                    $chatId,
                    "❌ No pudimos encontrar ningún profesional registrado con el teléfono o email: <b>{$arg}</b>.\n\nAsegúrate de ingresar el mismo número con el prefijo internacional (ej. +34600111222) o el email de tu perfil."
                );
            }
        } else {
            // Respuesta genérica para otros textos
            $this->telegram->enviarTextoLibre(
                $chatId,
                "🤖 Soy el bot de notificaciones de CitasPro. No puedo procesar mensajes de texto libre.\n\nSi necesitas vincular tu cuenta, usa el comando /start."
            );
        }

        return response()->json(['success' => true]);
    }

    /**
     * Maneja las interacciones con botones inline (Callback Queries)
     */
    private function handleCallbackQuery(array $callbackQuery): JsonResponse
    {
        $callbackQueryId = $callbackQuery['id'];
        $data            = $callbackQuery['data'] ?? '';
        $message         = $callbackQuery['message'] ?? null;
        $chatId          = $message['chat']['id'] ?? null;
        $messageId       = $message['message_id'] ?? null;

        if (!$chatId || !$messageId || empty($data)) {
            return response()->json(['success' => true]);
        }

        // Sintaxis del callback_data: "accion_cita:ID_CITA"
        if (!str_contains($data, ':')) {
            $this->telegram->responderCallbackQuery($callbackQueryId, 'Acción no válida');
            return response()->json(['success' => true]);
        }

        [$action, $citaId] = explode(':', $data, 2);

        $cita = Cita::find($citaId);
        if (!$cita) {
            $this->telegram->responderCallbackQuery($callbackQueryId, 'Cita no encontrada');
            return response()->json(['success' => true]);
        }

        // Seguridad: Asegurar que el profesional de la cita corresponda al chat que pulsa el botón
        if ((string) $cita->profesional->telegram_chat_id !== (string) $chatId) {
            $this->telegram->responderCallbackQuery($callbackQueryId, 'No tienes permisos sobre esta cita.');
            return response()->json(['success' => true]);
        }

        // Validar si ya está procesada
        if ($cita->estado === 'confirmada' && $action === 'confirmar_cita') {
            $this->telegram->responderCallbackQuery($callbackQueryId, 'Esta cita ya estaba confirmada.');
            $this->actualizarMensajeTelegramSinBotones($cita, $chatId, $messageId, 'confirmada');
            return response()->json(['success' => true]);
        }

        if ($cita->estado === 'cancelada' && $action === 'rechazar_cita') {
            $this->telegram->responderCallbackQuery($callbackQueryId, 'Esta cita ya estaba cancelada/rechazada.');
            $this->actualizarMensajeTelegramSinBotones($cita, $chatId, $messageId, 'cancelada');
            return response()->json(['success' => true]);
        }

        if ($action === 'confirmar_cita') {
            $cita->estado = 'confirmada';
            $cita->save();

            // Disparar evento para notificar al cliente (WhatsApp/SMS)
            event(new CitaActualizada($cita, ['estado' => 'confirmada']));

            $this->telegram->responderCallbackQuery($callbackQueryId, 'Cita confirmada correctamente.');
            $this->actualizarMensajeTelegramSinBotones($cita, $chatId, $messageId, 'confirmada');
        } elseif ($action === 'rechazar_cita') {
            $cita->estado = 'cancelada';
            $cita->save();

            // Disparar evento para notificar cancelación
            event(new CitaCancelada($cita, 'Rechazada desde Telegram', 'negocio'));

            $this->telegram->responderCallbackQuery($callbackQueryId, 'Cita rechazada.');
            $this->actualizarMensajeTelegramSinBotones($cita, $chatId, $messageId, 'cancelada');
        }

        return response()->json(['success' => true]);
    }

    /**
     * Actualiza el texto en Telegram y elimina los botones.
     */
    private function actualizarMensajeTelegramSinBotones(Cita $cita, int|string $chatId, int $messageId, string $nuevoEstado): void
    {
        $icono = $nuevoEstado === 'confirmada' ? '✅' : '❌';
        $estadoLabel = $nuevoEstado === 'confirmada' ? 'CONFIRMADA' : 'RECHAZADA';

        $fechaFormato = \Carbon\Carbon::parse($cita->fecha)->translatedFormat('l d \de F \de Y');
        $horaInicio   = substr($cita->hora_inicio, 0, 5);
        $horaFin      = substr($cita->hora_fin, 0, 5);
        $notas        = empty($cita->notas_cliente) ? '' : "\n💬 <b>Notas del cliente:</b>\n<i>{$cita->notas_cliente}</i>";

        $nuevoTexto = <<<MSG
        {$icono} <b>CITA {$estadoLabel}</b>

        📋 <b>Referencia:</b> <code>{$cita->codigo_referencia}</code>
        👤 <b>Cliente:</b> {$cita->cliente->nombre_completo}
        📞 <b>Teléfono:</b> <code>{$cita->cliente->telefono}</code>
        💼 <b>Servicio:</b> {$cita->servicio->nombre}
        📅 <b>Fecha:</b> {$fechaFormato}
        🕐 <b>Hora:</b> {$horaInicio} – {$horaFin}
        ⏱️ <b>Duración:</b> {$cita->duracion_min} min
        💰 <b>Precio:</b> {$cita->precio_total} {$cita->moneda}
        🏪 <b>Negocio:</b> {$cita->negocio->nombre}

        {$notas}
        MSG;

        $this->telegram->editarMensaje($chatId, $messageId, $nuevoTexto);
    }

    /**
     * Helper para normalizar el número de teléfono eliminando caracteres no deseados
     */
    private function normalizarTelefono(string $telefono): string
    {
        $tel = preg_replace('/[^\d+]/', '', $telefono);
        // Si no tiene el +, agregárselo asumiendo formato internacional válido
        if (!str_starts_with($tel, '+') && strlen($tel) > 6) {
            $tel = '+' . $tel;
        }
        return $tel;
    }
}
