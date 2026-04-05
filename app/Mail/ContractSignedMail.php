<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContractSignedMail extends Mailable
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $contractTitle,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sözleşmeniz İmzalandı ✅');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contract-signed');
    }
}
