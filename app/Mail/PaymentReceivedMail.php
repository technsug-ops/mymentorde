<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Manager "ödeme alındı" olarak işaretlediğinde aday/öğrenciye giden teyit maili.
 * "Paranız bize ulaştı, sürecinize başlıyoruz" niteliğinde.
 */
class PaymentReceivedMail extends Mailable
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $contractTitle,
        public readonly ?string $contractNo,
        public readonly ?string $paymentAmountText = null,
        public readonly ?string $paymentDateText = null,   // örn. "29.04.2026"
        public readonly ?string $portalUrl = null,
        public readonly ?string $advisorName = null,        // varsa atanmış danışman adı
    ) {}

    public function envelope(): Envelope
    {
        $subject = 'Ödemeniz Bize Ulaştı — Sürecinize Başlıyoruz';
        if ($this->contractNo) {
            $subject .= ' — #' . $this->contractNo;
        }
        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.payment-received');
    }
}
