<?php

namespace App\Providers;

use App\Events\CitaCreada;
use App\Events\CitaActualizada;
use App\Events\CitaCancelada;
use App\Listeners\NotificarProfesionalTelegram;
use App\Listeners\EnviarConfirmacionCliente;
use App\Listeners\NotificarCancelacion;
use App\Listeners\SincronizarGoogleCalendar;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Mapa de eventos → listeners para CitasPro.
     *
     * Cada evento puede tener múltiples listeners.
     * Los listeners con ShouldQueue se ejecutan en cola asíncrona.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ── Auth ─────────────────────────────────────────────────────────────
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // ── Cita Creada ───────────────────────────────────────────────────────
        // Dispara: confirmación al cliente + notificación Telegram al profesional + sync Google Calendar
        CitaCreada::class => [
            EnviarConfirmacionCliente::class . '@handleCitaCreada',
            NotificarProfesionalTelegram::class . '@handleCitaCreada',
            SincronizarGoogleCalendar::class . '@handleCitaCreada',
            \App\Listeners\EnviarEmailsConfirmacion::class . '@handleCitaCreada',
        ],

        // ── Cita Actualizada ─────────────────────────────────────────────────
        // Dispara: notificación de cambio al cliente + actualización al profesional + sync Google Calendar
        CitaActualizada::class => [
            EnviarConfirmacionCliente::class . '@handleCitaActualizada',
            NotificarProfesionalTelegram::class . '@handleCitaActualizada',
            SincronizarGoogleCalendar::class . '@handleCitaActualizada',
        ],

        // ── Cita Cancelada ───────────────────────────────────────────────────
        // Dispara: notificación de cancelación a cliente Y profesional + delete de Google Calendar
        CitaCancelada::class => [
            NotificarCancelacion::class,
            SincronizarGoogleCalendar::class . '@handleCitaCancelada',
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

