<?php

namespace App\Listeners;

use App\Events\CitaCreada;
use App\Mail\CitaConfirmacionMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EnviarEmailsConfirmacion implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notificaciones';
    public int $tries = 3;
    public int $backoff = 15;

    /**
     * Handle the event.
     */
    public function handleCitaCreada(CitaCreada $event): void
    {
        $cita = $event->cita->load(['cliente', 'servicio', 'negocio', 'profesional']);

        Log::info("EnviarEmailsConfirmacion: Iniciando envío de correos para cita #{$cita->id}");

        // 1. Enviar al Paciente (si tiene correo electrónico)
        if ($cita->cliente->email) {
            try {
                Mail::to($cita->cliente->email)->send(new CitaConfirmacionMail($cita, 'paciente'));
                Log::info("EnviarEmailsConfirmacion: Correo enviado al paciente ({$cita->cliente->email})");
            } catch (\Throwable $e) {
                Log::error("EnviarEmailsConfirmacion: Falló el envío al paciente ({$cita->cliente->email}). Error: " . $e->getMessage());
            }
        }

        // 2. Enviar al Negocio / Clínica (si tiene correo electrónico)
        if ($cita->negocio->email) {
            try {
                Mail::to($cita->negocio->email)->send(new CitaConfirmacionMail($cita, 'clinica'));
                Log::info("EnviarEmailsConfirmacion: Correo enviado a la clínica ({$cita->negocio->email})");
            } catch (\Throwable $e) {
                Log::error("EnviarEmailsConfirmacion: Falló el envío a la clínica ({$cita->negocio->email}). Error: " . $e->getMessage());
            }
        }
    }
}
