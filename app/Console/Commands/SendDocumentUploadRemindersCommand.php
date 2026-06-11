<?php

namespace App\Console\Commands;

use App\Mail\DocumentUploadReminderMail;
use App\Models\DocumentUploadToken;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * D7: Doc-request token hatirlatma — saatlik tarama.
 *
 * 2 stage:
 *   first: created_at + 24h gecmis, hala used_count=0, reminder_first_sent_at=null
 *          (hatirlatma gonder, timestamp set et)
 *   final: expires_at - 1h <= now <= expires_at, used_count=0, reminder_final_sent_at=null
 *
 * Idempotent: timestamp set edildiginde tekrar gonderilmez.
 *
 * D6: WhatsApp kanali destegi — token.notification_channels['whatsapp'] varsa
 * paralel olarak WhatsApp mesaji da gider. Email + WhatsApp ayri timestamp'lar
 * tutar; idempotency her kanal icin ayri calisir (biri basarisiz olursa
 * digerine engel olmaz).
 */
class SendDocumentUploadRemindersCommand extends Command
{
    protected $signature = 'doc-request:send-reminders {--dry-run : sadece sayim/preview, mail/WhatsApp gonderme}';
    protected $description = 'Belge talep linklerine 24h ve final hatirlatmalari email + WhatsApp ile gonderir.';

    public function handle(WhatsAppService $whatsapp): int
    {
        $now    = Carbon::now();
        $dryRun = (bool) $this->option('dry-run');

        $firstWindowStart = $now->copy()->subHours(30);
        $firstWindowEnd   = $now->copy()->subHours(24);

        // ── First reminder (email scope: email kanali aktif + recipient_email var) ──
        $firstEmail = DocumentUploadToken::query()
            ->whereNotNull('recipient_email')
            ->where('used_count', 0)
            ->whereNull('reminder_first_sent_at')
            ->whereBetween('created_at', [$firstWindowStart, $firstWindowEnd])
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->get();

        // ── First reminder (whatsapp scope: whatsapp kanali aktif + recipient_phone var) ──
        $firstWhatsApp = DocumentUploadToken::query()
            ->whereNotNull('recipient_phone')
            ->where('used_count', 0)
            ->whereNull('whatsapp_first_sent_at')
            ->whereBetween('created_at', [$firstWindowStart, $firstWindowEnd])
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->get();

        $this->info("[first] Email kayit: {$firstEmail->count()}, WhatsApp kayit: {$firstWhatsApp->count()}");

        $sentFirstEmail = 0;
        foreach ($firstEmail as $token) {
            if (!$token->hasChannel('email')) continue;
            if ($dryRun) {
                $this->line("  - [dry] would email first to {$token->recipient_email} (token #{$token->id})");
                continue;
            }
            try {
                Mail::to($token->recipient_email)->queue(new DocumentUploadReminderMail($token, 'first'));
                $token->forceFill(['reminder_first_sent_at' => $now])->save();
                $sentFirstEmail++;
            } catch (Throwable $e) {
                $this->error("  ! token #{$token->id} email fail: " . $e->getMessage());
            }
        }

        $sentFirstWa = 0;
        foreach ($firstWhatsApp as $token) {
            if (!$token->hasChannel('whatsapp')) continue;
            if ($dryRun) {
                $this->line("  - [dry] would WhatsApp first to token #{$token->id} ({$token->recipient_phone})");
                continue;
            }
            try {
                $url  = url('/u/' . $token->token);
                $hrs  = $token->expires_at ? max(0, (int) round($now->diffInHours($token->expires_at, false))) : 0;
                $sent = $whatsapp->sendDocumentRequestReminder(
                    (string) $token->recipient_phone,
                    $url,
                    (string) ($token->category_name ?? 'belge'),
                    $hrs
                );
                if ($sent) {
                    $token->forceFill(['whatsapp_first_sent_at' => $now])->save();
                    $sentFirstWa++;
                } else {
                    $this->warn("  ! token #{$token->id} WhatsApp first skip/fail");
                }
            } catch (Throwable $e) {
                $this->error("  ! token #{$token->id} WhatsApp fail: " . $e->getMessage());
            }
        }

        // ── Final reminder (email) ──
        $finalEmail = DocumentUploadToken::query()
            ->whereNotNull('recipient_email')
            ->whereNotNull('expires_at')
            ->where('used_count', 0)
            ->whereNull('reminder_final_sent_at')
            ->whereBetween('expires_at', [$now, $now->copy()->addHour()])
            ->get();

        // ── Final reminder (whatsapp) ──
        $finalWhatsApp = DocumentUploadToken::query()
            ->whereNotNull('recipient_phone')
            ->whereNotNull('expires_at')
            ->where('used_count', 0)
            ->whereNull('whatsapp_final_sent_at')
            ->whereBetween('expires_at', [$now, $now->copy()->addHour()])
            ->get();

        $this->info("[final] Email kayit: {$finalEmail->count()}, WhatsApp kayit: {$finalWhatsApp->count()}");

        $sentFinalEmail = 0;
        foreach ($finalEmail as $token) {
            if (!$token->hasChannel('email')) continue;
            if ($dryRun) {
                $this->line("  - [dry] would email final to {$token->recipient_email} (token #{$token->id})");
                continue;
            }
            try {
                Mail::to($token->recipient_email)->queue(new DocumentUploadReminderMail($token, 'final'));
                $token->forceFill(['reminder_final_sent_at' => $now])->save();
                $sentFinalEmail++;
            } catch (Throwable $e) {
                $this->error("  ! token #{$token->id} email fail: " . $e->getMessage());
            }
        }

        $sentFinalWa = 0;
        foreach ($finalWhatsApp as $token) {
            if (!$token->hasChannel('whatsapp')) continue;
            if ($dryRun) {
                $this->line("  - [dry] would WhatsApp final to token #{$token->id} ({$token->recipient_phone})");
                continue;
            }
            try {
                $url  = url('/u/' . $token->token);
                $sent = $whatsapp->sendDocumentRequestReminder(
                    (string) $token->recipient_phone,
                    $url,
                    (string) ($token->category_name ?? 'belge'),
                    1 // son uyari — yaklasik 1 saat kaldi
                );
                if ($sent) {
                    $token->forceFill(['whatsapp_final_sent_at' => $now])->save();
                    $sentFinalWa++;
                } else {
                    $this->warn("  ! token #{$token->id} WhatsApp final skip/fail");
                }
            } catch (Throwable $e) {
                $this->error("  ! token #{$token->id} WhatsApp fail: " . $e->getMessage());
            }
        }

        $this->info(sprintf(
            'first: email=%d, whatsapp=%d | final: email=%d, whatsapp=%d',
            $sentFirstEmail, $sentFirstWa, $sentFinalEmail, $sentFinalWa
        ));

        return self::SUCCESS;
    }
}
