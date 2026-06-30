<?php

namespace App\Listeners;

use App\Events\CitaCreada;
use App\Events\CitaActualizada;
use App\Events\CitaCancelada;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

/**
 * NotificarProfesionalTelegram
 *
 * Escucha eventos de citas y notifica al profesional vía Telegram
 * de forma inmediata y asíncrona (usando la cola de Laravel).
 *
 * Implementa ShouldQueue para no bloquear la respuesta HTTP al cliente.
 */
class NotificarProfesionalTelegram
{

    public function __construct(
        private readonly TelegramService $telegram
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Handlers de eventos
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Maneja el evento CitaCreada.
     * Notifica al profesional sobre la nueva reserva.
     */
    public function handleCitaCreada(CitaCreada $event): void
    {
        $cita = $event->cita->load(['cliente', 'servicio', 'negocio', 'profesional']);

        Log::info("NotificarProfesionalTelegram: Nueva cita #{$cita->id} → Profesional: {$cita->profesional->nombre}");

        $this->telegram->notificarNuevaCita($cita);
    }

    /**
     * Maneja el evento CitaActualizada.
     * Notifica al profesional sobre la modificación.
     */
    public function handleCitaActualizada(CitaActualizada $event): void
    {
        $cita    = $event->cita->load(['cliente', 'servicio', 'negocio', 'profesional']);
        $cambios = $event->cambios;

        Log::info("NotificarProfesionalTelegram: Cita #{$cita->id} modificada → Profesional: {$cita->profesional->nombre}");

        $this->telegram->notificarModificacionCita($cita, $cambios);
    }

    /**
     * Maneja el evento CitaCancelada.
     * Notifica al profesional sobre la cancelación.
     */
    public function handleCitaCancelada(CitaCancelada $event): void
    {
        $cita   = $event->cita->load(['cliente', 'servicio', 'negocio', 'profesional']);
        $motivo = $event->motivo;

        Log::info("NotificarProfesionalTelegram: Cita #{$cita->id} cancelada → Profesional: {$cita->profesional->nombre}");

        $this->telegram->notificarCancelacionCita($cita, $motivo);
    }

    /**
     * Maneja errores: si Telegram falla 3 veces, se loguea sin detener el sistema.
     */
    public function failed(\Throwable $e): void
    {
        Log::error("NotificarProfesionalTelegram: Listener falló definitivamente.", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
