<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo con el código OTP de 6 dígitos para recuperar o cambiar la
 * contraseña (mismo flujo para ambos puntos de entrada).
 */
class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $usuario,
        public readonly string $codigo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de verificación de BovWeight CR',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.otp-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
