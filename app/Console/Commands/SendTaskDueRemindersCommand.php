<?php

namespace App\Console\Commands;

use App\Models\MarketingTask;
use App\Models\NotificationDispatch;
use Illuminate\Console\Command;

class SendTaskDueRemindersCommand extends Command
{
    protected $signature = 'tasks:send-due-reminders {--dry-run : Bildirim göndermeden listele}';
    protected $description = 'Yarın / bugün / geçmiş due_date olan task\'lar için bildirim gönderir (her gün 08:00)';

    public function handle(): int
    {
        $today    = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $tasks = MarketingTask::query()
            ->withoutGlobalScope('company')
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('assigned_user_id')
            ->whereNotNull('due_date')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($today, $tomorrow) {
                $q->whereDate('due_date', $tomorrow)   // yarın vadesi dolar
                  ->orWhereDate('due_date', $today)    // bugün vadesi dolar
                  ->orWhereDate('due_date', '<', $today); // geçmiş, hâlâ açık
            })
            ->with('assigned:id,name,role')
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('tasks:send-due-reminders | sent=0 (görev yok)');
            return 0;
        }

        $sent = 0;
        foreach ($tasks as $task) {
            $userId = (int) $task->assigned_user_id;
            if ($userId <= 0) {
                continue;
            }

            $dueDate   = (string) $task->due_date;
            $urgency   = match (true) {
                $dueDate < $today    => 'GECIKTI',
                $dueDate === $today  => 'BUGÜN',
                default              => 'YARIN',
            };

            $body = "[{$urgency}] Task #{$task->id}: {$task->title} — son tarih: {$dueDate}";

            if ($this->option('dry-run')) {
                $this->line("  [DRY-RUN] user={$userId} | {$body}");
                $sent++;
                continue;
            }

            // Aynı gün için zaten bildirim gönderilmişse tekrar gönderme
            $alreadySent = NotificationDispatch::query()
                ->where('user_id', $userId)
                ->where('subject', 'Task Hatırlatma')
                ->where('body', $body)
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            NotificationDispatch::create([
                'user_id'    => $userId,
                'channel'    => 'in_app',
                'subject'    => 'Task Hatırlatma',
                'body'       => $body,
                'status'     => 'pending',
                'company_id' => $task->company_id,
            ]);

            $sent++;
        }

        $this->info("tasks:send-due-reminders | sent={$sent}");
        return 0;
    }
}
