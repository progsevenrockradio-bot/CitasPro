<?php

namespace App\Events;

use App\Models\Cita;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: CitaCancelada
 * Se dispara cuando una cita es cancelada (por el cliente o el negocio).
 */
class CitaCancelada
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Cita   $cita,
        public readonly string $motivo = '',
        public readonly string $canceladaPor = 'cliente' // 'cliente' | 'negocio' | 'sistema'
    ) {}
}
