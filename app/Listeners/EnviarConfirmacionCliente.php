<?php

namespace App\Listeners;

use App\Events\CitaCreada;
use App\Events\CitaActualizada;
use App\Services\WhatsAppService;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * EnviarConfirmacionCliente
 *
 * Envía confirmación al cliente vía WhatsApp (o SMS de respaldo)
 * cuando se crea o modifica una cita.
 *
 * Implementa ShouldQueue para ejecución asíncrona.
 */
class EnviarConfirmacionCliente implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue   = 'notificaciones';
    public int    $tries   = 3;
    public int    $backoff = 15;

    public function __construct(
        private readonly WhatsAppService $whatsapp,
        private readonly SmsService      $sms,
    ) {}

    /**
     * Maneja el evento CitaCreada.
     * Envía confirmación al cliente.
     */
    public function handleCitaCreada(CitaCreada $event): void
    {
        $cita = $event->cita->load(['cliente', 'servicio', 'negocio', 'profesional']);

        Log::info("EnviarConfirmacionCliente: Cita #{$cita->id} → Cliente: {$cita->cliente->nombre}");

        $enviado = $this->whatsapp->enviarConfirmacion($cita);

        if (!$enviado) {
            Log::warning("EnviarConfirmacionCliente: WhatsApp falló. Usando SMS de respaldo...");
            $this->sms->enviarConfirmacion($cita);
        }
    }

    /**
     * Maneja el evento CitaActualizada.
     * Notifica al cliente del cambio en su cita.
     */
    public function handleCitaActualizada(CitaActualizada $event): void
    {
        $cita = $event->cita->load(['cliente', 'servicio', 'negocio']);

        $hora  = substr($cita->hora_inicio, 0, 5);
        $fecha = $cita->fecha->format('d/m/Y');

        $mensaje = "CitasPro: Tu cita ha sido modificada. "
                 . "{$cita->servicio->nombre} ahora el {$fecha} a las {$hora}. "
                 . "Ref: {$cita->codigo_referencia}";

        $enviado = $this->whatsapp->enviarTexto($cita->cliente->telefono, $mensaje);

        if (!$enviado) {
            $this->sms->enviar($cita->cliente->telefono, $mensaje);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("EnviarConfirmacionCliente: Listener falló.", ['error' => $e->getMessage()]);
    }
}
