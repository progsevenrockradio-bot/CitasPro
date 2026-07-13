<?php

namespace App\Listeners;

use App\Events\PagoConfirmado;
use App\Mail\PagoConfirmacionMail;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Escucha el evento PagoConfirmado y envía notificaciones automáticas:
 *   1. Email de confirmación al cliente (con el recibo)
 *   2. WhatsApp al cliente
 *   3. Email al negocio informando del pago recibido
 */
class NotificarPagoConfirmado implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly WhatsAppService $whatsapp
    ) {}

    public function handle(PagoConfirmado $event): void
    {
        $pago = $event->pago->load(['cliente', 'negocio', 'cita.servicio', 'cita.profesional']);

        // ── 1. Email al Cliente ──────────────────────────────────────
        try {
            if ($pago->cliente?->email) {
                Mail::to($pago->cliente->email)
                    ->send(new PagoConfirmacionMail($pago, 'cliente'));
                Log::info("NotificarPagoConfirmado: Email enviado al cliente #{$pago->cliente->id} — pago #{$pago->id}");
            }
        } catch (\Throwable $e) {
            Log::error("NotificarPagoConfirmado: Error al enviar email al cliente — pago #{$pago->id}: {$e->getMessage()}");
        }

        // ── 2. WhatsApp al Cliente ───────────────────────────────────
        try {
            if ($pago->cliente?->telefono) {
                $this->whatsapp->enviarConfirmacionPago($pago);
                Log::info("NotificarPagoConfirmado: WhatsApp enviado al cliente — pago #{$pago->id}");
            }
        } catch (\Throwable $e) {
            Log::error("NotificarPagoConfirmado: Error al enviar WhatsApp — pago #{$pago->id}: {$e->getMessage()}");
        }

        // ── 3. Email al Negocio ──────────────────────────────────────
        try {
            if ($pago->negocio?->email) {
                Mail::to($pago->negocio->email)
                    ->send(new PagoConfirmacionMail($pago, 'negocio'));
                Log::info("NotificarPagoConfirmado: Email enviado al negocio #{$pago->negocio->id} — pago #{$pago->id}");
            }
        } catch (\Throwable $e) {
            Log::error("NotificarPagoConfirmado: Error al enviar email al negocio — pago #{$pago->id}: {$e->getMessage()}");
        }
    }

    /**
     * Manejar fallo del job tras agotar los reintentos.
     */
    public function failed(PagoConfirmado $event, \Throwable $exception): void
    {
        Log::error("NotificarPagoConfirmado: Job fallido definitivamente — pago #{$event->pago->id}: {$exception->getMessage()}");
    }
}
