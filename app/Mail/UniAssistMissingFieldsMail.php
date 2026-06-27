<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Uni-Assist başvurusu için eksik alanları öğrenciye sorma maili.
 * Manager rehber sayfasından "öğrenciden iste" tıkladığında gönderilir.
 */
class UniAssistMissingFieldsMail extends Mailable
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
        return new Envelope(subject: 'Uni-Assist başvurun için bazı bilgiler gerekli');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.uni-assist-missing-fields');
    }
}
