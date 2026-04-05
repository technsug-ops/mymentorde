<?php

namespace App\Console\Commands;

use App\Models\DmThread;
use App\Models\MarketingTask;
use App\Models\NotificationDispatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessTaskAutomationCommand extends Command
{
    protected $signature = 'tasks:process-automation {--limit=200 : Max records per operation}';
    protected $description = 'Generate recurring tasks and enqueue overdue task escalations';

    public function handle(): int
    {
        $limit   = max(1, (int) $this->option('limit'));
        $now     = Carbon::now();
        $dueReminderWindowEnd = $now->copy()->addHours(24);

        $nextRunAtFor = function (MarketingTask $task, Carbon $from): Carbon {
            $pattern      = (string) ($task->recurrence_pattern ?: 'weekly');
            $intervalDays = max(1, (int) ($task->recurrence_interval_days ?? 7));
            return match ($pattern) {
                'daily'   => $from->copy()->addDays($intervalDays),
                'monthly' => $from->copy()->addMonths(max(1, (int) ceil($intervalDays / 30))),
                default   => $from->copy()->addWeeks(max(1, (int) ceil($intervalDays / 7))),
            };
        };

        // ── Recurring task generation ─────────────────────────────────────────
        $recurringRows = MarketingTask::query()
            ->where('is_recurring', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get();

        $generated = 0;
        foreach ($recurringRows as $row) {
            $alreadyGenerated = MarketingTask::query()
                ->where('parent_task_id', $row->id)
                ->where('is_auto_generated', true)
                ->whereDate('created_at', $now->toDateString())
                ->exists();

            if (!$alreadyGenerated) {
                $nextRunAt   = $nextRunAtFor($row, Carbon::parse($row->next_run_at));
                $baseDueDate = $row->due_date
                    ? Carbon::parse($row->due_date)->addDays(max(1, (int) ($row->recurrence_interval_days ?? 7)))
                    : $nextRunAt->copy()->startOfDay();

                MarketingTask::query()->create([
                    'company_id'               => (int) ($row->company_id ?? 0),
                    'title'                    => (string) $row->title,
                    'description'              => (string) ($row->description ?? ''),
                    'status'                   => 'todo',
                    'priority'                 => (string) ($row->priority ?? 'normal'),
                    'due_date'                 => $baseDueDate->toDateString(),
                    'assigned_user_id'         => $row->assigned_user_id,
                    'created_by_user_id'       => $row->created_by_user_id,
                    'is_recurring'             => false,
                    'recurrence_pattern'       => null,
                    'recurrence_interval_days' => null,
                    'next_run_at'              => null,
                    'escalate_after_hours'     => (int) ($row->escalate_after_hours ?? 24),
                    'last_escalated_at'        => null,
                    'parent_task_id'           => $row->id,
                    'is_auto_generated'        => true,
                ]);
                $generated++;
            }

            $row->update(['next_run_at' => $nextRunAtFor($row, Carbon::parse($row->next_run_at))]);
        }

        // ── Due reminders ─────────────────────────────────────────────────────
        $reminded    = 0;
        $dueSoonRows = MarketingTask::query()
            ->where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->whereNotNull('assigned_user_id')
            ->whereDate('due_date', '>=', $now->toDateString())
            ->whereDate('due_date', '<=', $dueReminderWindowEnd->toDateString())
            ->orderBy('due_date')
            ->limit($limit)
            ->get();

        foreach ($dueSoonRows as $row) {
            $assignee = User::query()->find((int) $row->assigned_user_id);
            if (!$assignee || empty($assignee->email)) { continue; }

            $reminderSourceId = $row->id . ':' . (string) $row->due_date;
            $alreadyQueued    = NotificationDispatch::query()
                ->where('source_type', 'marketing_task_due_reminder')
                ->where('source_id', $reminderSourceId)
                ->whereIn('status', ['queued', 'sent'])
                ->exists();
            if ($alreadyQueued) { continue; }

            NotificationDispatch::query()->create([
                'channel'         => 'email',
                'category'        => 'task_due_reminder',
                'recipient_email' => (string) $assignee->email,
                'recipient_name'  => (string) ($assignee->name ?? ''),
                'subject'         => 'Task due reminder: #' . $row->id . ' ' . $row->title,
                'body'            => 'Task termin yaklasiyor. Task #' . $row->id . ' | baslik: ' . $row->title . ' | termin: ' . $row->due_date,
                'variables'       => ['task_id' => $row->id, 'task_title' => (string) $row->title, 'due_date' => (string) $row->due_date, 'priority' => (string) $row->priority],
                'status'          => 'queued',
                'queued_at'       => $now,
                'source_type'     => 'marketing_task_due_reminder',
                'source_id'       => $reminderSourceId,
                'triggered_by'    => 'system:tasks_automation',
            ]);
            $reminded++;
        }

        // ── Task escalation ───────────────────────────────────────────────────
        $escalated   = 0;
        $overdueRows = MarketingTask::query()
            ->where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $now->toDateString())
            ->whereNotNull('assigned_user_id')
            ->orderBy('due_date')
            ->limit($limit)
            ->get();

        foreach ($overdueRows as $row) {
            $escalateAfterHours = max(1, (int) ($row->escalate_after_hours ?? 24));
            $dueAt = Carbon::parse($row->due_date)->endOfDay();
            if ($now->lt($dueAt->copy()->addHours($escalateAfterHours))) { continue; }
            if ($row->last_escalated_at) {
                $last = Carbon::parse($row->last_escalated_at);
                if ($last->diffInHours($now) < $escalateAfterHours) { continue; }
            }

            $assignee = User::query()->find((int) $row->assigned_user_id);
            if (!$assignee || empty($assignee->email)) { continue; }

            NotificationDispatch::query()->create([
                'channel'         => 'email',
                'category'        => 'task_escalation',
                'recipient_email' => (string) $assignee->email,
                'recipient_name'  => (string) ($assignee->name ?? ''),
                'subject'         => 'Task escalation: #' . $row->id . ' ' . $row->title,
                'body'            => 'Task gecikmesi tespit edildi. Task #' . $row->id . ' | baslik: ' . $row->title . ' | termin: ' . $row->due_date,
                'variables'       => ['task_id' => $row->id, 'task_title' => (string) $row->title, 'due_date' => (string) $row->due_date, 'priority' => (string) $row->priority],
                'status'          => 'queued',
                'queued_at'       => $now,
                'source_type'     => 'marketing_task',
                'source_id'       => (string) $row->id,
                'triggered_by'    => 'system:tasks_automation',
            ]);
            $row->update(['last_escalated_at' => $now]);
            $escalated++;
        }

        // ── DM SLA escalation ─────────────────────────────────────────────────
        $dmEscalated = 0;
        $dmRows      = DmThread::query()
            ->where('status', 'open')
            ->whereNotNull('next_response_due_at')
            ->where('next_response_due_at', '<', $now)
            ->orderBy('next_response_due_at')
            ->limit($limit)
            ->get();

        foreach ($dmRows as $thread) {
            $advisorId = (int) ($thread->advisor_user_id ?? 0);
            if ($advisorId <= 0) { continue; }
            $advisor = User::query()->find($advisorId);
            if (!$advisor || empty($advisor->email)) { continue; }

            $alreadyQueued = NotificationDispatch::query()
                ->where('source_type', 'dm_sla_escalation')
                ->where('source_id', (string) $thread->id)
                ->where('status', 'queued')
                ->exists();
            if ($alreadyQueued) { continue; }

            NotificationDispatch::query()->create([
                'channel'         => 'email',
                'category'        => 'conversation_sla',
                'recipient_email' => (string) $advisor->email,
                'recipient_name'  => (string) ($advisor->name ?? ''),
                'subject'         => 'DM SLA Escalation: Thread #' . $thread->id,
                'body'            => 'Yanit SLA suresi asildi. Thread #' . $thread->id . ' icin donus bekleniyor.',
                'variables'       => ['thread_id' => (int) $thread->id, 'department' => (string) ($thread->department ?? '-'), 'due_at' => (string) ($thread->next_response_due_at ?? '')],
                'status'          => 'queued',
                'queued_at'       => now(),
                'source_type'     => 'dm_sla_escalation',
                'source_id'       => (string) $thread->id,
                'triggered_by'    => 'system:tasks_automation',
            ]);

            MarketingTask::query()
                ->withoutGlobalScope('company')
                ->firstOrCreate(
                    ['company_id' => (int) ($thread->company_id ?: 1), 'source_type' => 'conversation_response_due', 'source_id' => (string) $thread->id, 'assigned_user_id' => $advisorId],
                    [
                        'title'                => 'DM yanit gecikmesi',
                        'description'          => 'Thread #' . $thread->id . ' icin SLA asimi var.',
                        'status'               => 'todo',
                        'priority'             => 'high',
                        'department'           => (string) ($thread->department ?: 'advisory'),
                        'due_date'             => now()->toDateString(),
                        'created_by_user_id'   => null,
                        'escalate_after_hours' => max(1, (int) ($thread->sla_hours ?: 24)),
                    ]
                );

            $dmEscalated++;
        }

        $this->info("tasks:process-automation tamamlandi | recurring_checked: {$recurringRows->count()} | generated: {$generated} | due_reminders: {$reminded} | escalated: {$escalated} | dm_escalated: {$dmEscalated}");

        return 0;
    }
}
