<?php

namespace App\Listeners;

use App\Events\CitaCreada;
use App\Events\CitaActualizada;
use App\Events\CitaCancelada;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SincronizarGoogleCalendar implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notificaciones';
    public int $tries = 3;
    public int $backoff = 15;

    public function __construct(private readonly GoogleCalendarService $googleCalendarService) {}

    /**
     * Sincroniza cuando una cita es creada.
     */
    public function handleCitaCreada(CitaCreada $event): void
    {
        $cita = $event->cita->load(['cliente', 'servicio', 'profesional']);
        Log::info("SincronizarGoogleCalendar: Cita creada #{$cita->id} → Sincronizando con Google...");
        $this->googleCalendarService->syncCitaToGoogle($cita);
    }

    /**
     * Sincroniza cuando una cita es actualizada.
     */
    public function handleCitaActualizada(CitaActualizada $event): void
    {
        $cita = $event->cita->load(['cliente', 'servicio', 'profesional']);
        Log::info("SincronizarGoogleCalendar: Cita actualizada #{$cita->id} → Sincronizando con Google...");
        $this->googleCalendarService->syncCitaToGoogle($cita);
    }

    /**
     * Sincroniza cuando una cita es cancelada o eliminada.
     */
    public function handleCitaCancelada(CitaCancelada $event): void
    {
        $cita = $event->cita->load(['profesional']);
        if ($cita->google_event_id) {
            Log::info("SincronizarGoogleCalendar: Cita cancelada #{$cita->id} → Eliminando de Google...");
            $this->googleCalendarService->deleteGoogleEvent($cita->profesional, $cita->google_event_id);
            // Limpiar el ID de evento localmente
            $cita->updateQuietly(['google_event_id' => null]);
        }
    }
}
