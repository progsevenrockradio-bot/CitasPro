<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Zona horaria por defecto para las tareas programadas.
     */
    protected function scheduleTimezone(): \DateTimeZone|string|null
    {
        return 'Europe/Madrid';
    }

    /**
     * Define the application's command schedule.
     *
     * Activar en el servidor (crontab -e):
     *   * * * * * docker exec -u 1000 -e HOME=/tmp web-php php /var/www/citaspro/artisan schedule:run >> /DATA/proyectos/citaspro/storage/logs/cron-schedule.log 2>&1
     */
    protected function schedule(Schedule $schedule): void
    {
        // ── Recordatorios de citas por correo (única tarea autorizada activa) ──
        // Se ejecuta cada hora en horario Europe/Madrid y envía un único aviso
        // por cita (ventana principal 24h y ventana 1h [0..90m] como respaldo).
        $schedule
            ->command('citas:recordatorios')
            ->timezone('Europe/Madrid')
            ->hourly()
            ->between('07:00', '22:00')
            ->withoutOverlapping(30)
            ->onOneServer()
            ->onFailure(function () {
                Log::error('Scheduler: citas:recordatorios falló.');
            })
            ->appendOutputTo(storage_path('logs/scheduler-recordatorios.log'));

        // Las tareas siguientes quedan expresamente deshabilitadas por defecto
        // hasta recibir autorización explícita (p. ej. resumen por Telegram y
        // purgas automáticas de OTPs/tokens).
        if ((bool) config('services.scheduler.habilitar_tareas_adicionales', false)) {
            $schedule
                ->command('citas:resumen-diario')
                ->timezone('Europe/Madrid')
                ->dailyAt('08:00')
                ->withoutOverlapping(30)
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/scheduler-resumen.log'));

            $schedule
                ->command('citas:limpiar-otps')
                ->timezone('Europe/Madrid')
                ->daily()
                ->at('03:00')
                ->withoutOverlapping(30)
                ->appendOutputTo(storage_path('logs/scheduler-limpieza.log'));

            $schedule
                ->command('sanctum:prune-expired --hours=720')
                ->timezone('Europe/Madrid')
                ->weekly()
                ->sundays()
                ->at('04:00');
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
