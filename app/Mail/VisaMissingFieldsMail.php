<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Almanya vize başvurusu için eksik alanları öğrenciye sorma maili.
 * Manager Vize Rehberi sayfasından "öğrenciden iste" tıkladığında gönderilir.
 */
class VisaMissingFieldsMail extends Mailable
{
    /**
     * @param  array<int,string>  $missingLabels
     */
    public function __construct(
        public readonly string $recipientName,
        public readonly array  $missingLabels,
        public readonly ?string $managerNote = null,
        public readonly ?string $portalUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Vize başvurun için bazı bilgiler gerekli');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.visa-missing-fields');
    }
}
