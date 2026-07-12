<?php

namespace App\Events;

use App\Models\Cita;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCompleted
{
    use Dispatchable, SerializesModels;

    public Cita $cita;

    /**
     * Create a new event instance.
     */
    public function __construct(Cita $cita)
    {
        $this->cita = $cita;
    }
}
