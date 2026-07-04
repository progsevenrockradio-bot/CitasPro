<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * OtpMailNotification — Email con el código PIN de verificación.
 *
 * Se envía cuando el profesional/cliente solicita acceso sin contraseña.
 */
class OtpMailNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $codigo        Código OTP de 6 dígitos
     * @param int    $expiraMinutos Minutos hasta que el código expira
     * @param string $nombreUsuario Nombre del destinatario (si se conoce)
     */
    public function __construct(
        public readonly string $codigo,
        public readonly int    $expiraMinutos = 10,
        public readonly string $nombreUsuario = 'Usuario'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de acceso CitasPro: ' . $this->codigo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
        );
    }
}
