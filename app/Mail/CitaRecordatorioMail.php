<?php

namespace App\Mail;

use App\Models\Cita;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CitaRecordatorioMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cita $cita;
    public string $etiqueta;

    /**
     * Create a new message instance.
     */
    public function __construct(Cita $cita, string $etiqueta = '24h')
    {
        $this->cita = $cita->loadMissing(['cliente', 'servicio', 'profesional', 'negocio']);
        $this->etiqueta = $etiqueta;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio de tu Cita - ' . $this->cita->negocio->nombre,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cita_recordatorio',
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
