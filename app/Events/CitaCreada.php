<?php

namespace App\Events;

use App\Models\Cita;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: CitaCreada
 * Se dispara cuando un cliente reserva una nueva cita.
 * Triggers:
 *   → NotificarProfesionalTelegram (listener)
 *   → EnviarConfirmacionCliente   (listener)
 */
class CitaCreada
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Cita $cita
    ) {}
}
