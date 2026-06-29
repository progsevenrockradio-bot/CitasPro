<?php

namespace App\Listeners;

use App\Events\CitaCancelada;
use App\Services\WhatsAppService;
use App\Services\TelegramService;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * NotificarCancelacion
 *
 * Notifica tanto al cliente (WhatsApp/SMS) como al profesional (Telegram)
 * cuando una cita es cancelada.
 */
class NotificarCancelacion implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue   = 'notificaciones';
    public int    $tries   = 3;
    public int    $backoff = 15;

    public function __construct(
        private readonly WhatsAppService $whatsapp,
        private readonly TelegramService $telegram,
        private readonly SmsService      $sms,
    ) {}

    public function handle(CitaCancelada $event): void
    {
        $cita   = $event->cita->load(['cliente', 'servicio', 'negocio', 'profesional']);
        $motivo = $event->motivo;

        Log::info("NotificarCancelacion: Cita #{$cita->id} cancelada por: {$event->canceladaPor}");

        // 1. Notificar al cliente (WhatsApp → SMS)
        $enviado = $this->whatsapp->enviarCancelacion($cita, $motivo);
        if (!$enviado) {
            $this->sms->enviarCancelacion($cita);
        }

        // 2. Notificar al profesional (Telegram) — solo si no fue él quien canceló
        if ($event->canceladaPor !== 'profesional') {
            $this->telegram->notificarCancelacionCita($cita, $motivo);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("NotificarCancelacion: Listener falló.", ['error' => $e->getMessage()]);
    }
}
