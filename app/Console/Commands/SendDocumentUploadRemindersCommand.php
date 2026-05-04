<?php

namespace App\Console\Commands;

use App\Mail\DocumentUploadReminderMail;
use App\Models\DocumentUploadToken;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * D7: Doc-request token hatirlatma maili — saatlik tarama.
 *
 * 2 stage:
 *   first: created_at + 24h gecmis, hala used_count=0, reminder_first_sent_at=null
 *          (hatirlatma gonder, timestamp set et)
 *   final: expires_at - 1h <= now <= expires_at, used_count=0, reminder_final_sent_at=null
 *
 * Idempotent: timestamp set edildiginde tekrar gonderilmez.
 * Sadece recipient_email varsa gonderir; phone-only token'lar atlanir
 * (D6 WhatsApp eklenince ayrilacak).
 */
class SendDocumentUploadRemindersCommand extends Command
{
    protected $signature = 'doc-request:send-reminders {--dry-run : sadece sayim/preview, mail gonderme}';
    protected $description = 'Belge talep linklerine 24h ve final hatirlatma mailleri gonderir.';

    public function handle(): int
    {
        $now    = Carbon::now();
        $dryRun = (bool) $this->option('dry-run');

        $firstWindowStart = $now->copy()->subHours(30);
        $firstWindowEnd   = $now->copy()->subHours(24);

        // ── First reminder: 24-30 saat once olusturulmus, hala kullanilmamis ──
        $first = DocumentUploadToken::query()
            ->whereNotNull('recipient_email')
            ->where('used_count', 0)
            ->whereNull('reminder_first_sent_at')
            ->whereBetween('created_at', [$firstWindowStart, $firstWindowEnd])
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', $now);
            })
            ->get();

        $this->info("[first] Kayit: {$first->count()}");
        $sentFirst = 0;
        foreach ($first as $token) {
            if ($dryRun) {
                $this->line("  - [dry] would send first to {$token->recipient_email} (token #{$token->id})");
                continue;
            }
            try {
                Mail::to($token->recipient_email)->queue(new DocumentUploadReminderMail($token, 'first'));
                $token->forceFill(['reminder_first_sent_at' => $now])->save();
                $sentFirst++;
            } catch (Throwable $e) {
                $this->error("  ! token #{$token->id} mail fail: " . $e->getMessage());
            }
        }

        // ── Final reminder: expires_at - 1h <= now <= expires_at ──
        // Token suresi NULL olanlar (sinirsiz) bu kontrole girmez
        $final = DocumentUploadToken::query()
            ->whereNotNull('recipient_email')
            ->whereNotNull('expires_at')
            ->where('used_count', 0)
            ->whereNull('reminder_final_sent_at')
            ->whereBetween('expires_at', [$now, $now->copy()->addHour()])
            ->get();

        $this->info("[final] Kayit: {$final->count()}");
        $sentFinal = 0;
        foreach ($final as $token) {
            if ($dryRun) {
                $this->line("  - [dry] would send final to {$token->recipient_email} (token #{$token->id})");
                continue;
            }
            try {
                Mail::to($token->recipient_email)->queue(new DocumentUploadReminderMail($token, 'final'));
                $token->forceFill(['reminder_final_sent_at' => $now])->save();
                $sentFinal++;
            } catch (Throwable $e) {
                $this->error("  ! token #{$token->id} mail fail: " . $e->getMessage());
            }
        }

        $this->info("first sent: {$sentFirst} / final sent: {$sentFinal}");
        return self::SUCCESS;
    }
}
