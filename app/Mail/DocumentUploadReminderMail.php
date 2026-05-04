<?php

namespace App\Mail;

use App\Models\DocumentUploadToken;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * D7: Belge talep linki hatirlatmasi.
 *
 * 2 ayri stage'de gonderilir:
 *   - 'first': link olusturulduktan 24-30 saat sonra, henuz tiklanmamis ise
 *   - 'final': expires_at'e 1 saat kala, hala tiklanmamis ise
 *
 * Idempotent: token'da reminder_*_sent_at NULL olmali.
 */
class DocumentUploadReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $uploadUrl;
    public string $categoryLabel;
    public string $hoursLeft;
    public string $stageLabel;
    public ?string $customMessage;

    public function __construct(
        public DocumentUploadToken $token,
        public string $stage = 'first', // 'first' | 'final'
    ) {
        $this->uploadUrl     = url('/document-upload/' . $token->token);
        $this->categoryLabel = (string) ($token->category_name ?: $token->category_code ?: 'Belge');
        $this->customMessage = $token->custom_message;

        $expiresAt = $token->expires_at instanceof CarbonImmutable
            ? $token->expires_at
            : CarbonImmutable::parse((string) $token->expires_at);
        $diffMinutes = max(0, (int) now()->diffInMinutes($expiresAt, false));
        if ($diffMinutes >= 60) {
            $this->hoursLeft = ((int) round($diffMinutes / 60)) . ' saat';
        } else {
            $this->hoursLeft = $diffMinutes . ' dakika';
        }

        $this->stageLabel = $stage === 'final'
            ? 'Son Hatırlatma — Süre Doluyor'
            : 'Hatırlatma — Belgeniz Bekleniyor';
    }

    public function envelope(): Envelope
    {
        $subject = $this->stage === 'final'
            ? "⏰ Son hatırlatma: {$this->categoryLabel} yüklemen için {$this->hoursLeft} kaldı"
            : "📎 {$this->categoryLabel} belgeni yüklemen bekleniyor";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.document-upload-reminder');
    }

    public function attachments(): array
    {
        return [];
    }
}
