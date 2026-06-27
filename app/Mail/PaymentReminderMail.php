<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sözleşme imzalandı ama ödeme yapılmadığında gönderilen kademeli hatırlatma.
 *
 * 5 seviye:
 *   1 → nazik ilk hatırlatma
 *   2 → ikinci tekrar
 *   3 → önemli (süreç askıda)
 *   4 → acil (sonraki son bildirim)
 *   5 → son bildirim: 15 gün ek süre, sonra sözleşme iptal +
 *        kısmi ödemeler servis bedeli olarak alıkonur
 *
 * Otomatik sıralı gönderim için ayrı bir Console Command + scheduler kurulmalı;
 * bu sınıf sadece tek bir hatırlatma gönderme görevini taşır.
 */
class PaymentReminderMail extends Mailable
{
    /**
     * @param  array<string,string>  $bankInfo  ['account_holder','bank_name','iban','bic','currency']
     * @param  int  $level         1..5 — hatırlatma seviyesi
     * @param  int  $daysOverdue   sözleşme imzalandığından beri geçen gün (sadece bilgi)
     * @param  int  $finalGraceDays  5. mail'de verilen ek süre (gün) — varsayılan 15
     */
    public function __construct(
        public readonly string $recipientName,
        public readonly string $contractTitle,
        public readonly ?string $contractNo,
        public readonly string $paymentAmountText,
        public readonly string $paymentReference,
        public readonly array $bankInfo,
        public readonly ?string $portalUrl,
        public readonly int $level,
        public readonly int $daysOverdue = 0,
        public readonly int $finalGraceDays = 15,
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            1 => 'Ödeme Hatırlatması — Sözleşmeniz',
            2 => 'İkinci Hatırlatma — Ödeme Bekleniyor',
            3 => 'Önemli: Süreciniz Ödeme Bekliyor',
            4 => 'Acil: Ödeme Aksiyonu Gerekli',
            5 => 'Son Bildirim — Sözleşme İptal Uyarısı',
        ];
        $subject = $subjects[$this->level] ?? 'Ödeme Hatırlatması';
        if ($this->contractNo) {
            $subject .= ' — #' . $this->contractNo;
        }
        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.payment-reminder');
    }
}
