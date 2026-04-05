<?php

namespace App\Console\Commands;

use App\Models\ScheduledNotification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * K3 — Zamanlanmış bildirimleri işle.
 * Schedule: her 5 dakikada bir çalışır (everyFiveMinutes).
 */
class ProcessScheduledNotificationsCommand extends Command
{
    protected $signature = 'notifications:process-scheduled {--dry-run : Gerçekten gönderme}';
    protected $description = 'Zamanı gelen zamanlanmış bildirimleri gönderir.';

    public function handle(NotificationService $notificationService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $schedules = ScheduledNotification::query()
            ->where('is_active', true)
            ->get();

        $sent = 0;

        foreach ($schedules as $schedule) {
            if (!$schedule->isDue()) {
                continue;
            }

            $recipients = $this->resolveRecipients($schedule);

            foreach ($recipients as $recipient) {
                $body = $schedule->renderBody([
                    'name'    => $recipient['name'] ?? '',
                    'email'   => $recipient['email'] ?? '',
                    'date'    => now()->format('d.m.Y'),
                    'company' => config('app.name', 'MentorDE'),
                ]);

                if (!$dryRun) {
                    $notificationService->send([
                        'channel'         => (string) $schedule->channel,
                        'category'        => (string) ($schedule->category ?? 'scheduled'),
                        'user_id'         => $recipient['user_id'] ?? null,
                        'recipient_email' => $recipient['email'] ?? null,
                        'subject'         => (string) ($schedule->subject ?? $schedule->name),
                        'body'            => $body,
                        'company_id'      => (int) ($schedule->company_id ?? 0),
                        'source_type'     => 'scheduled_notification',
                        'source_id'       => (string) $schedule->id,
                        'triggered_by'    => 'system:scheduler',
                    ]);
                }

                $sent++;
                $this->line("  → {$recipient['email']}");
            }

            if (!$dryRun) {
                $schedule->update([
                    'last_sent_at' => now(),
                    'sent_count'   => $schedule->sent_count + 1,
                ]);

                // Tek seferlik → deaktif et
                if ($schedule->schedule_type === 'once') {
                    $schedule->update(['is_active' => false]);
                }
            }
        }

        $prefix = $dryRun ? '[DRY-RUN] ' : '';
        $this->info("{$prefix}Zamanlanmış bildirimler işlendi: {$sent} gönderim.");

        return self::SUCCESS;
    }

    private function resolveRecipients(ScheduledNotification $schedule): array
    {
        if ($schedule->target_email) {
            $user = User::where('email', $schedule->target_email)->first();
            return [[
                'email'   => $schedule->target_email,
                'name'    => $user?->name ?? $schedule->target_email,
                'user_id' => $user?->id,
            ]];
        }

        if ($schedule->target_role) {
            $companyId = (int) ($schedule->company_id ?? 0);
            return User::query()
                ->where('role', $schedule->target_role)
                ->where('is_active', true)
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->get(['id', 'name', 'email'])
                ->map(fn ($u) => [
                    'email'   => $u->email,
                    'name'    => $u->name,
                    'user_id' => $u->id,
                ])
                ->all();
        }

        return [];
    }
}
