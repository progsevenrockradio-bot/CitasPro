<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CitasListEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $citas;
    public $negocio;
    public $mensaje;
    protected $pdfContent;

    /**
     * Create a new message instance.
     */
    public function __construct($citas, $negocio, $mensaje = null, $pdfContent = null)
    {
        $this->citas = $citas;
        $this->negocio = $negocio;
        $this->mensaje = $mensaje;
        $this->pdfContent = $pdfContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Listado de Citas - ' . $this->negocio->nombre,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.citas_list',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->pdfContent) {
            $attachments[] = Attachment::fromData(fn () => $this->pdfContent, 'Listado_Citas.pdf')
                    ->withMime('application/pdf');
        }

        return $attachments;
    }
}
