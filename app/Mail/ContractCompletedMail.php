<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;

/**
 * Sözleşme tamamlandı bildirimi — manager onayladığında karşı tarafa gider.
 * İçerikte sözleşme PDF'i + varsa ek maddeler/dökümanlar ek olarak gönderilir.
 */
class ContractCompletedMail extends Mailable
{
    /**
     * @param  array<int,string>  $attachmentPaths  Storage 'local' disk'inde göreceli yollar
     * @param  array<int,string>  $annexNotes       Sözleşmeye ait ek metinler (mail body'sinde gösterilir)
     * @param  array<string,string>  $bankInfo      ['account_holder','bank_name','iban','bic','currency']
     */
    public function __construct(
        public readonly string $recipientName,
        public readonly string $contractTitle,
        public readonly ?string $contractNo = null,
        public readonly array $attachmentPaths = [],
        public readonly array $annexNotes = [],
        public readonly ?string $portalUrl = null,
        // Ödeme bilgisi — manager onayında sözleşme mailine ödeme detayı eklenir
        public readonly ?string $paymentAmountText = null,    // örn. "2.490 EUR"
        public readonly ?string $paymentReference = null,     // örn. "AHMET YILMAZ #STU-00000123"
        public readonly array $bankInfo = [],                 // banka detayları (config('brand.banking'))
        public readonly int $paymentDueDays = 14,             // ödeme süresi (gün)
    ) {}

    public function envelope(): Envelope
    {
        $subject = 'Sözleşmeniz Tamamlandı';
        if ($this->contractNo) {
            $subject .= ' — #' . $this->contractNo;
        }
        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contract-completed');
    }

    /**
     * @return array<int,Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->attachmentPaths as $path) {
            $path = trim((string) $path);
            if ($path === '') continue;
            if (!Storage::disk('local')->exists($path)) continue;

            $attachments[] = Attachment::fromStorageDisk('local', $path)
                ->as(basename($path));
        }
        return $attachments;
    }
}
