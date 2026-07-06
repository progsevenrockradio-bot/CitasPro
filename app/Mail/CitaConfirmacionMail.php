<?php

namespace App\Mail;

use App\Models\Cita;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CitaConfirmacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cita $cita;
    public string $rol;

    /**
     * Create a new message instance.
     */
    public function __construct(Cita $cita, string $rol)
    {
        $this->cita = $cita;
        $this->rol = $rol;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->rol === 'paciente' 
            ? "Confirmación de tu Cita - {$this->cita->negocio->nombre}"
            : "Nueva Cita Registrada - Ref: {$this->cita->codigo_referencia}";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cita_confirmacion',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
