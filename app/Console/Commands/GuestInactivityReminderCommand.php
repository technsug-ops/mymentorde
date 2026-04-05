<?php

namespace App\Console\Commands;

use App\Models\GuestApplication;
use App\Models\NotificationDispatch;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GuestInactivityReminderCommand extends Command
{
    protected $signature = 'guest:inactivity-reminder
                            {--days=7 : Kaç gün işlem yapılmamışsa hatırlatma gönderilsin}
                            {--dry-run : Sadece sayıları göster, gönderme}';

    protected $description = 'Belirli gün boyunca işlem yapmayan guest başvurularına hatırlatma bildirimi gönderir';

    public function handle(): int
    {
        $days    = (int) $this->option('days');
        $dryRun  = (bool) $this->option('dry-run');
        $cutoff  = Carbon::now()->subDays($days);

        // Aktif (submitted veya under_review) başvurular; son updated_at cutoff'tan önce
        $applications = GuestApplication::query()
            ->whereIn('status', ['submitted', 'under_review'])
            ->where('updated_at', '<=', $cutoff)
            ->whereNull('deleted_at')
            ->with('user')
            ->get();

        if ($applications->isEmpty()) {
            $this->info("Hatırlatma gönderilecek başvuru yok ({$days} günden fazla inaktif).");
            return 0;
        }

        $this->info("Bulunan inaktif başvuru sayısı: {$applications->count()} ({$days}+ gün)");

        if ($dryRun) {
            $this->warn('--dry-run aktif, bildirim gönderilmedi.');
            return 0;
        }

        $sent = 0;

        foreach ($applications as $app) {
            $userId = $app->user_id ?? null;
            if (!$userId) {
                continue;
            }

            // Aynı kullanıcıya son 24 saat içinde aynı tip bildirim gönderilmiş mi?
            $alreadySent = NotificationDispatch::query()
                ->where('user_id', $userId)
                ->where('template_key', 'inactivity_reminder')
                ->where('created_at', '>=', Carbon::now()->subHours(24))
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $name     = $app->user?->name ?? 'Başvuru Sahibi';
            $inactivedays = (int) Carbon::parse($app->updated_at)->diffInDays(Carbon::now());

            NotificationDispatch::create([
                'user_id'          => $userId,
                'company_id'       => $app->company_id,
                'template_key'     => 'inactivity_reminder',
                'channel'          => 'in_app',
                'recipient_email'  => $app->user?->email,
                'message_subject'  => 'Başvurunuzu Tamamlamayı Unutmayın',
                'message_body'     => "Sayın {$name}, başvurunuzda {$inactivedays} gündür işlem yapılmamış. Danışmanınız sizi bekliyor — şimdi devam edin.",
                'status'           => 'pending',
                'scheduled_at'     => now(),
            ]);

            $sent++;
        }

        $this->info("Gönderilen hatırlatma: {$sent}");
        return 0;
    }
}
