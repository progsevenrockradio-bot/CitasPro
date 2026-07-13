<?php

namespace App\Events;

use App\Models\Pago;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se dispara cuando un pago de cita es confirmado como "completado".
 * Sus Listeners enviarán notificaciones automáticas al cliente y al negocio.
 */
class PagoConfirmado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Pago $pago
    ) {}
}
