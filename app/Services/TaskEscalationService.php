<?php

namespace App\Services;

use App\Models\MarketingTask;
use App\Models\TaskActivityLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class TaskEscalationService
{
    public function __construct(private readonly NotificationService $notificationService) {}
    private const MAX_LEVEL = 3;

    public function checkAndEscalate(): int
    {
        $escalated = 0;

        $tasks = MarketingTask::query()
            ->withoutGlobalScope('company')
            ->whereIn('status', ['todo', 'in_progress', 'blocked'])
            ->where(function ($q): void {
                $q->whereNull('completed_at');
            })
            ->where('escalation_level', '<', self::MAX_LEVEL)
            ->get();

        foreach ($tasks as $task) {
            if ($this->shouldEscalate($task)) {
                $this->escalate($task);
                $escalated++;
            }
        }

        return $escalated;
    }

    private function shouldEscalate(MarketingTask $task): bool
    {
        $slaHours      = max(1, (int) ($task->escalate_after_hours ?? 24));
        $level         = (int) ($task->escalation_level ?? 0);
        $multiplier    = $level + 1;
        $thresholdHours = $slaHours * $multiplier;

        $baseline = $task->last_escalated_at ?? $task->created_at;
        if (! $baseline) {
            return false;
        }

        return Carbon::parse($baseline)->addHours($thresholdHours)->isPast();
    }

    private function escalate(MarketingTask $task): void
    {
        $oldLevel = (int) ($task->escalation_level ?? 0);
        $newLevel = $oldLevel + 1;

        $updates = [
            'escalation_level'  => $newLevel,
            'last_escalated_at' => now(),
        ];

        // Seviye 3'te priority urgent'a yükselt
        if ($newLevel >= self::MAX_LEVEL && (string) $task->priority !== 'urgent') {
            $updates['priority'] = 'urgent';
        }

        $task->update($updates);

        TaskActivityLog::record(
            (int) $task->id,
            null,
            'escalated',
            'level:' . $oldLevel,
            'level:' . $newLevel
        );

        $this->sendEscalationNotifications($task, $newLevel);
    }

    private function sendEscalationNotifications(MarketingTask $task, int $level): void
    {
        $companyId = (int) ($task->company_id ?? 0);
        $subject   = 'Task Eskalasyonu (Seviye ' . $level . ')';
        $body      = 'Task #' . $task->id . ' "' . $task->title . '" SLA süresi aşıldı. Eskalasyon seviyesi: ' . $level . '.';

        $recipients = collect();

        // Seviye 1: atanan kişi + dept admin
        $assignedUserId = (int) ($task->assigned_user_id ?? 0);
        if ($assignedUserId > 0) {
            $recipients->push($assignedUserId);
        }

        if ($level >= 1) {
            // Departman admin'ini bul
            $deptAdminRoles = $this->deptAdminRolesFor((string) ($task->department ?? ''));
            $admins = User::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->whereIn('role', $deptAdminRoles)
                ->where('is_active', true)
                ->pluck('id');
            $recipients = $recipients->merge($admins);
        }

        if ($level >= 2) {
            // Manager
            $managers = User::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('role', User::ROLE_MANAGER)
                ->where('is_active', true)
                ->pluck('id');
            $recipients = $recipients->merge($managers);
        }

        $category = 'task_escalation_level' . min($level, 3);

        foreach ($recipients->unique() as $recipientId) {
            $this->notificationService->send([
                'channel'      => 'in_app',
                'category'     => $category,
                'user_id'      => (int) $recipientId,
                'company_id'   => $companyId ?: null,
                'subject'      => $subject,
                'body'         => $body,
                'source_type'  => 'marketing_task',
                'source_id'    => (string) $task->id,
                'triggered_by' => 'system',
            ]);
        }
    }

    private function deptAdminRolesFor(string $department): array
    {
        return match ($department) {
            'operations' => [User::ROLE_OPERATIONS_ADMIN, User::ROLE_MANAGER],
            'finance'    => [User::ROLE_FINANCE_ADMIN, User::ROLE_MANAGER],
            'advisory'   => [User::ROLE_MANAGER],
            'marketing'  => [User::ROLE_MARKETING_ADMIN, User::ROLE_SALES_ADMIN, User::ROLE_MANAGER],
            'system'     => [User::ROLE_SYSTEM_ADMIN, User::ROLE_MANAGER],
            default      => [User::ROLE_MANAGER],
        };
    }
}
