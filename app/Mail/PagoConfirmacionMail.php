<?php

namespace App\Mail;

use App\Models\Pago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PagoConfirmacionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Pago   $pago  El pago completado (con relaciones cargadas: cliente, negocio, cita.servicio)
     * @param string $rol   'cliente' o 'negocio'
     */
    public function __construct(
        public readonly Pago $pago,
        public readonly string $rol = 'cliente'
    ) {}

    public function envelope(): Envelope
    {
        $negocioNombre = $this->pago->negocio?->nombre ?? 'CitasPro';

        $subject = $this->rol === 'cliente'
            ? "✅ Pago confirmado — {$negocioNombre}"
            : "💰 Pago recibido — {$this->pago->cita?->codigo_referencia}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pago_confirmacion',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
