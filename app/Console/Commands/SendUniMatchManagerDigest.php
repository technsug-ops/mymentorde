<?php

namespace App\Console\Commands;

use App\Models\NotificationDispatch;
use App\Models\UniMatchResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Manager'a günlük UniMatch lead özet maili.
 *
 * Akış:
 *  - Önceki gün (00:00–23:59) yeni lead bırakılan UniMatch responses
 *  - Manager + Marketing admin rolündeki user'lara gönderilir
 *  - "Bugün X yeni lead, Y wizard tamamlandı, Z kayıt oldu" özeti
 *  - İlk 10 lead'in iletişim bilgisi tabloda
 *
 * Cron: günlük 08:00.
 */
class SendUniMatchManagerDigest extends Command
{
    protected $signature = 'unimatch:send-manager-digest
        {--dry-run : Sadece raporla, gönderme}';

    protected $description = 'Manager + Marketing admin rolüne UniMatch günlük lead özet maili';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $yesterday = Carbon::yesterday();
        $start = $yesterday->copy()->startOfDay();
        $end = $yesterday->copy()->endOfDay();

        $base = UniMatchResponse::query()
            ->where('started_at', '>=', $start)
            ->where('started_at', '<=', $end);

        $stats = [
            'date'      => $yesterday->format('d.m.Y'),
            'started'   => (clone $base)->count(),
            'leads'     => (clone $base)->whereNotNull('lead_captured_at')->count(),
            'completed' => (clone $base)->whereNotNull('completed_at')->count(),
            'converted' => (clone $base)->whereNotNull('converted_at')->count(),
        ];

        // Hiç aktivite yoksa mail gönderme
        if ($stats['started'] === 0) {
            $this->info("Önceki gün ({$stats['date']}) hiç UniMatch aktivitesi yok — mail gönderilmedi.");
            return self::SUCCESS;
        }

        $leads = (clone $base)
            ->whereNotNull('lead_captured_at')
            ->orderByDesc('lead_captured_at')
            ->limit(10)
            ->get();

        // Hedef kullanıcı: manager + marketing_admin rolü
        $recipients = User::query()
            ->whereIn('role', ['manager', 'marketing_admin', 'system_admin'])
            ->whereNotNull('email')
            ->where('is_active', true)
            ->get();

        $this->info("Stats: {$stats['started']} start / {$stats['leads']} lead / {$stats['converted']} kayıt");
        $this->info("Hedef alıcı: {$recipients->count()} (manager/marketing/system)");

        $sent = 0;
        foreach ($recipients as $user) {
            $templateKey = 'unimatch_manager_digest_' . $yesterday->format('Y_m_d');
            $alreadySent = NotificationDispatch::query()
                ->where('user_id', $user->id)
                ->where('template_key', $templateKey)
                ->exists();

            if ($alreadySent) continue;

            if ($dryRun) {
                $this->line("  [dry] {$user->email}");
                continue;
            }

            try {
                Mail::send('emails.unimatch.manager_digest', [
                    'firstName' => $user->name,
                    'stats'     => $stats,
                    'leads'     => $leads,
                    'funnelUrl' => url('/manager/unimatch-funnel'),
                ], function ($m) use ($user, $stats) {
                    $subject = "📊 UniMatch Günlük Özet — {$stats['date']} · {$stats['started']} aktivite, {$stats['leads']} lead";
                    $m->to($user->email, $user->name)->subject($subject);
                });

                NotificationDispatch::create([
                    'user_id'      => $user->id,
                    'template_key' => $templateKey,
                    'channel'      => 'email',
                    'email_to'     => $user->email,
                    'subject'      => "UniMatch Özet {$stats['date']}",
                    'status'       => 'sent',
                    'sent_at'      => now(),
                ]);

                $sent++;
            } catch (\Throwable $e) {
                $this->warn("  ⚠ {$user->email}: " . $e->getMessage());
            }
        }

        if ($dryRun) {
            $this->warn('--dry-run aktif.');
        } else {
            $this->info("✅ {$sent} özet maili gönderildi.");
        }

        return self::SUCCESS;
    }
}
