<?php

namespace App\Http\Controllers\TaskBoard\Concerns;

use App\Models\MarketingTask;
use App\Models\TaskActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

trait TaskBoardTrait
{
    /** Statüye göre timestamp/flag alanlarını hesaplar. */
    protected function buildStatusPayload(MarketingTask $row, string $newStatus, int $userId, array $extra = []): array
    {
        return match ($newStatus) {
            'done'      => ['completed_at' => $row->completed_at ?: now(), 'hold_reason' => null],
            'cancelled' => ['cancelled_at' => now(), 'cancelled_by_user_id' => $userId, 'hold_reason' => null],
            'in_review' => ['review_requested_at' => $row->review_requested_at ?: now()],
            'on_hold'   => ['hold_reason' => $extra['hold_reason'] ?? $row->hold_reason, 'completed_at' => null],
            'todo'      => ['completed_at' => null, 'cancelled_at' => null, 'cancelled_by_user_id' => null, 'hold_reason' => null],
            default     => ['completed_at' => null],
        };
    }

    /** Bağımlı task'ları serbest bırak */
    protected function resolveDependents(int $taskId, int $userId): void
    {
        $this->resolveDependentsChain(MarketingTask::find($taskId), $userId, 0);
    }

    /** Zincirleme bağımlılık çözümü — max depth 5 */
    protected function resolveDependentsChain(?MarketingTask $task, int $userId, int $depth): void
    {
        if ($task === null || $depth >= 5) {
            return;
        }

        $dependents = MarketingTask::query()
            ->where('depends_on_task_id', $task->id)
            ->where('status', 'blocked')
            ->get();

        foreach ($dependents as $dep) {
            $dep->update(['status' => 'todo', 'depends_on_task_id' => null]);
            TaskActivityLog::record((int) $dep->id, $userId, 'dependency_completed', 'blocked', 'todo');
            $this->resolveDependentsChain($dep, $userId, $depth + 1);
        }
    }

    protected function canManage(Request $request, MarketingTask $row): bool
    {
        $user   = $request->user();
        $role   = (string) ($user->role ?? '');
        $userId = (int) $user->id;

        $currentCompanyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        if ($currentCompanyId > 0 && (int) ($row->company_id ?? 0) !== $currentCompanyId) {
            return false;
        }

        if ($this->isGlobalViewer($role)) {
            return true;
        }

        $roleScopedDepartment = $this->resolveScopedDepartmentForRole($role);
        if ($this->isDeptAdmin($role) && $roleScopedDepartment !== null) {
            return (string) ($row->department ?? '') === $roleScopedDepartment;
        }

        if ($roleScopedDepartment !== null && (string) ($row->department ?? '') !== $roleScopedDepartment) {
            return false;
        }

        return ((int) $row->assigned_user_id === $userId)
            || ((int) $row->created_by_user_id === $userId);
    }

    protected function canViewTask(Request $request, MarketingTask $row): bool
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');

        $currentCompanyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        if ($currentCompanyId > 0 && (int) ($row->company_id ?? 0) !== $currentCompanyId) {
            return false;
        }

        if ($this->isGlobalViewer($role)) {
            return true;
        }

        $roleScopedDepartment = $this->resolveScopedDepartmentForRole($role);
        if ($roleScopedDepartment === null) {
            return false;
        }

        return (string) ($row->department ?? '') === $roleScopedDepartment;
    }

    protected function isGlobalViewer(string $role): bool
    {
        return in_array($role, [User::ROLE_MANAGER, User::ROLE_SYSTEM_ADMIN], true);
    }

    protected function isDeptAdmin(string $role): bool
    {
        return in_array($role, [
            User::ROLE_OPERATIONS_ADMIN,
            User::ROLE_FINANCE_ADMIN,
            User::ROLE_MARKETING_ADMIN,
            User::ROLE_SALES_ADMIN,
        ], true);
    }

    protected function resolveScopedDepartmentForRole(string $role): ?string
    {
        return match ($role) {
            User::ROLE_OPERATIONS_ADMIN, User::ROLE_OPERATIONS_STAFF => 'operations',
            User::ROLE_FINANCE_ADMIN, User::ROLE_FINANCE_STAFF       => 'finance',
            User::ROLE_MARKETING_ADMIN, User::ROLE_MARKETING_STAFF,
            User::ROLE_SALES_ADMIN, User::ROLE_SALES_STAFF           => 'marketing',
            User::ROLE_SENIOR, User::ROLE_MENTOR                     => 'advisory',
            User::ROLE_SYSTEM_STAFF                                   => 'system',
            default                                                    => null,
        };
    }

    /** @return array<string, string> */
    protected function statusOptions(): array
    {
        return [
            'todo'        => 'Yapılacak',
            'in_progress' => 'Devam Ediyor',
            'in_review'   => 'İncelemede',
            'on_hold'     => 'Beklemede',
            'blocked'     => 'Bloke',
            'done'        => 'Tamamlandı',
            'cancelled'   => 'İptal',
        ];
    }

    /** @return array<string, string> */
    protected function priorityOptions(): array
    {
        return [
            'low'    => 'Dusuk',
            'normal' => 'Normal',
            'high'   => 'Yuksek',
            'urgent' => 'Acil',
        ];
    }

    /** @return array<string, string> */
    protected function departmentOptions(): array
    {
        return [
            'operations' => 'Operasyon',
            'finance'    => 'Finans',
            'advisory'   => 'Danismanlik',
            'marketing'  => 'Marketing',
            'system'     => 'Sistem',
        ];
    }

    /** @return array<string, string> */
    protected function processTypeOptions(): array
    {
        return MarketingTask::PROCESS_TYPES;
    }

    /** @return array<string, string> */
    protected function sourceOptions(): array
    {
        return [
            'guest_registration_submit'      => 'Guest Form Submit',
            'guest_contract_requested'       => 'Guest Contract Requested',
            'guest_contract_sales_followup'  => 'Contract Sales Followup',
            'guest_contract_signed_uploaded' => 'Guest Signed Contract Uploaded',
            'guest_document_uploaded'        => 'Guest Document Uploaded',
            'student_assignment_upsert'      => 'Student Assignment Updated',
            'student_process_outcome_created'=> 'Student Process Outcome Created',
            'student_document_uploaded'      => 'Student Document Uploaded',
            'student_onboarding_auto'        => 'Student Onboarding Auto',
            'student_step_request'           => 'Student Step Request',
            'manager_request_created'        => 'Manager Request',
            'conversation_quick_request'     => 'Conversation Quick Request',
            'conversation_response_due'      => 'Conversation Response Due',
            'conversation_message'           => 'Conversation Message Notification',
            'workflow_automation'            => 'Marketing Workflow',
            'lead_scoring_tier_change'       => 'Lead Score Tier Change',
            'recurring_clone'                => 'Tekrarlayan Görev Klonu',
        ];
    }

    protected function assignees(string $role, int $currentUserId)
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        if (! $this->isDeptAdmin($role) && ! $this->isGlobalViewer($role)) {
            return User::query()->where('id', $currentUserId)->get(['id', 'name', 'email', 'role']);
        }

        return User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', User::TASK_ACCESS_ROLES)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }

    /** Tahmin doğruluğu */
    protected function estimateAccuracy($tasks): ?float
    {
        $withBoth = $tasks->filter(fn ($t) => $t->actual_hours > 0 && $t->estimated_hours > 0);
        if ($withBoth->isEmpty()) return null;

        $avgError = $withBoth->avg(fn ($t) => abs($t->actual_hours - $t->estimated_hours) / $t->estimated_hours);

        return round((1 - $avgError) * 100, 1);
    }
}
