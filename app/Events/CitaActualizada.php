<?php

namespace App\Events;

use App\Models\Cita;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: CitaActualizada
 * Se dispara cuando se modifica fecha/hora/servicio de una cita.
 * $cambios contiene un array descriptivo de qué cambió.
 */
class CitaActualizada
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Cita  $cita,
        public readonly array $cambios = []
    ) {}
}
