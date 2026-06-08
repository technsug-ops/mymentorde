<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Manager paneli üzerinden tetiklenen şifre sıfırlama. Tek kullanımlık
 * temp şifre içerir; girişten sonra zorunlu değişim middleware'i
 * kullanıcıyı /password/change-required'a yönlendirir.
 */
class PasswordResetByManagerMail extends Mailable
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $tempPassword,
        public readonly string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Şifren sıfırlandı — yeni giriş bilgilerin');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.password-reset-by-manager',
            with: [
                'name'         => $this->name,
                'email'        => $this->email,
                'tempPassword' => $this->tempPassword,
                'loginUrl'     => $this->loginUrl,
            ],
        );
    }
}
