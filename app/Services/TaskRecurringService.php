<?php

namespace App\Services;

use App\Models\MarketingTask;
use App\Models\TaskActivityLog;

class TaskRecurringService
{
    public function processRecurring(): int
    {
        $cloned = 0;

        $tasks = MarketingTask::query()
            ->withoutGlobalScope('company')
            ->where('is_recurring', true)
            ->where('next_run_at', '<=', now())
            ->whereNull('deleted_at')
            ->get();

        foreach ($tasks as $task) {
            if ($this->cloneTask($task)) {
                $cloned++;
            }
        }

        return $cloned;
    }

    private function cloneTask(MarketingTask $task): bool
    {
        $slaHours = (int) ($task->escalate_after_hours ?? MarketingTask::defaultSlaHours((string) ($task->priority ?? 'normal')));

        $clone = MarketingTask::query()->withoutGlobalScope('company')->create([
            'company_id'           => $task->company_id,
            'title'                => (string) $task->title,
            'description'          => (string) ($task->description ?? ''),
            'status'               => 'todo',
            'priority'             => (string) ($task->priority ?? 'normal'),
            'department'           => (string) ($task->department ?? 'operations'),
            'due_date'             => now()->addHours($slaHours)->toDateString(),
            'assigned_user_id'     => $task->assigned_user_id,
            'created_by_user_id'   => $task->created_by_user_id,
            'is_recurring'         => false,
            'parent_task_id'       => (int) $task->id,
            'is_auto_generated'    => true,
            'source_type'          => 'recurring_clone',
            'source_id'            => (string) $task->id,
            'escalate_after_hours' => $slaHours,
        ]);

        TaskActivityLog::record((int) $clone->id, null, 'created', null, 'recurring_clone:' . $task->id);

        // Orijinal task'ın next_run_at'ını güncelle
        $pattern  = (string) ($task->recurrence_pattern ?? 'weekly');
        $interval = max(1, (int) ($task->recurrence_interval_days ?? 7));
        $addDays  = match ($pattern) {
            'daily'   => $interval,
            'monthly' => $interval * 30,
            default   => $interval * 7, // weekly
        };

        $task->update(['next_run_at' => now()->addDays($addDays)]);

        return true;
    }
}
