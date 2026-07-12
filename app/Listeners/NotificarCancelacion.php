<?php

namespace App\Listeners;

use App\Events\CitaCancelada;
use App\Services\WhatsAppService;
use App\Services\TelegramService;
use App\Services\SmsService;
use App\Mail\CitaCanceladaMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * NotificarCancelacion
 *
 * Notifica tanto al cliente (WhatsApp/SMS) como al profesional (Telegram)
 * cuando una cita es cancelada.
 */
class NotificarCancelacion implements ShouldQueue
{
    use InteractsWithQueue;


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

        // 1.5 Notificar al cliente (Email)
        if ($cita->cliente && !empty($cita->cliente->email)) {
            try {
                Mail::to($cita->cliente->email)->send(new CitaCanceladaMail($cita, 'paciente', $motivo));
            } catch (\Exception $e) {
                Log::error("Error enviando email de cancelación al cliente: " . $e->getMessage());
            }
        }

        // 2. Notificar al profesional (Telegram) — solo si no fue él quien canceló
        if ($event->canceladaPor !== 'profesional') {
            $this->telegram->notificarCancelacionCita($cita, $motivo);
        }

        // 2.5 Notificar al profesional (Email)
        if ($cita->profesional && !empty($cita->profesional->email)) {
            try {
                Mail::to($cita->profesional->email)->send(new CitaCanceladaMail($cita, 'profesional', $motivo));
            } catch (\Exception $e) {
                Log::error("Error enviando email de cancelación al profesional: " . $e->getMessage());
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("NotificarCancelacion: Listener falló.", ['error' => $e->getMessage()]);
    }
}
