<?php

namespace App\Console;

use App\Console\Commands\RecordarCitasCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * Activar en el servidor (crontab -e):
     *   * * * * * cd /path/to/citaspro && php artisan schedule:run >> /dev/null 2>&1
     *
     * En Windows (Task Scheduler) cada minuto ejecutar:
     *   php C:\laragon\www\CitasPro\artisan schedule:run
     */
    protected function schedule(Schedule $schedule): void
    {
        // ── Recordatorios de citas ────────────────────────────────────────────
        // Ejecuta cada hora para detectar citas en ventana de 24h y 1h
        $schedule
            ->command('citas:recordar')
            ->hourly()
            ->between('07:00', '22:00')         // Solo en horario razonable
            ->withoutOverlapping()               // No ejecutar si ya está corriendo
            ->onOneServer()                      // En clúster, solo un servidor
            ->onFailure(function () {
                Log::error('Scheduler: citas:recordar falló.');
            })
            ->appendOutputTo(storage_path('logs/scheduler-recordatorios.log'));

        // ── Resumen diario al staff (08:00 AM) ────────────────────────────────
        // Notifica a cada profesional sus citas del día via Telegram
        $schedule
            ->command('citas:resumen-diario')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler-resumen.log'));

        // ── Limpieza de OTPs expirados ────────────────────────────────────────
        // Elimina códigos OTP vencidos para mantener la tabla limpia
        $schedule
            ->command('citas:limpiar-otps')
            ->daily()
            ->at('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/scheduler-limpieza.log'));

        // ── Limpieza de tokens Sanctum expirados (cada semana) ────────────────
        $schedule
            ->command('sanctum:prune-expired --hours=720') // 30 días
            ->weekly()
            ->sundays()
            ->at('04:00');
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

