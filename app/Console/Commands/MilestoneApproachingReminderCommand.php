<?php

namespace App\Console\Commands;

use App\Models\GuestApplication;
use App\Models\GuestTimelineMilestone;
use App\Models\NotificationDispatch;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MilestoneApproachingReminderCommand extends Command
{
    protected $signature = 'guest:milestone-reminders
                            {--dry-run : Sadece sayıları göster, gönderme}';

    protected $description = '7 / 3 / 1 gün kalan guest milestone\'ları için hatırlatma bildirimi gönderir';

    /** Kaç gün kala bildirim gönderilsin */
    private const THRESHOLDS = [7, 3, 1];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $sent   = 0;
        $today  = Carbon::today();

        foreach (self::THRESHOLDS as $days) {
            $targetDate = $today->copy()->addDays($days)->toDateString();
            $templateKey = "milestone_approaching_{$days}d";

            $milestones = GuestTimelineMilestone::query()
                ->whereNull('completed_at')
                ->whereDate('target_date', $targetDate)
                ->with('guestApplication.user')
                ->get();

            foreach ($milestones as $milestone) {
                $guest = $milestone->guestApplication;
                if (!$guest || $guest->is_archived || !$guest->user_id) {
                    continue;
                }

                // Son 24 saat içinde aynı milestone için bildirim gönderilmiş mi?
                $alreadySent = NotificationDispatch::query()
                    ->where('user_id', $guest->user_id)
                    ->where('template_key', $templateKey)
                    ->where('source_type', 'milestone')
                    ->where('source_id', (string) $milestone->id)
                    ->where('created_at', '>=', Carbon::now()->subHours(24))
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $name  = $guest->user?->name ?? $guest->first_name ?? 'Başvuru Sahibi';
                $label = $milestone->label;

                $dayText = match ($days) {
                    1 => 'yarın',
                    3 => '3 gün sonra',
                    7 => '1 hafta sonra',
                    default => "{$days} gün sonra",
                };

                $body = "Sayın {$name}, «{$label}» adımı için son tarihiniz {$dayText}. "
                    . "Bu adımı zamanında tamamlamak başvuru sürecinizi hızlandırır.";

                $this->line("  [{$days}g] {$label} → {$name} (guest #{$guest->id})");

                if ($dryRun) {
                    continue;
                }

                NotificationDispatch::create([
                    'user_id'         => $guest->user_id,
                    'company_id'      => $guest->company_id,
                    'template_key'    => $templateKey,
                    'channel'         => 'in_app',
                    'recipient_email' => $guest->user?->email,
                    'message_subject' => "⏰ Hatırlatma: {$label} ({$dayText})",
                    'message_body'    => $body,
                    'status'          => 'pending',
                    'scheduled_at'    => now(),
                    'source_type'     => 'milestone',
                    'source_id'       => (string) $milestone->id,
                    'variables'       => json_encode([
                        'milestone_code' => $milestone->milestone_code,
                        'days_remaining' => $days,
                        'target_date'    => $milestone->target_date->toDateString(),
                    ]),
                ]);

                $sent++;
            }
        }

        if ($dryRun) {
            $this->warn('--dry-run aktif, bildirim gönderilmedi.');
        } else {
            $this->info("Gönderilen milestone hatırlatması: {$sent}");
        }

        return 0;
    }
}
