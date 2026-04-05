<?php

namespace App\Services;

use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class NotificationScopeService
{
    /**
     * Rol-Kategori eşleme haritası.
     * Her rol yalnızca bu kategorilerdeki bildirimleri görebilir.
     */
    private const ROLE_CATEGORY_MAP = [
        // Süper Roller: tüm kategoriler
        'manager'      => '*',
        'system_admin' => '*',

        // Admin Roller: kendi departman kategorileri
        'operations_admin' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_escalation_level1', 'task_escalation_level2',
            'task_escalation_level3', 'task_dependency_completed',
            'contract_requested', 'contract_signed_uploaded',
            'contract_approved', 'contract_rejected', 'contract_cancelled',
            'guest_registration_confirmation',
        ],
        'finance_admin' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_escalation_level1', 'task_escalation_level2',
            'task_escalation_level3', 'task_dependency_completed',
            'dealer_payout_pending',
        ],
        'marketing_admin' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_escalation_level1', 'task_escalation_level2',
            'task_escalation_level3', 'task_dependency_completed',
            'lead_score_tier_change', 'ab_test_significant',
            'email_campaign', 'workflow_notification', 'campaign_completed',
        ],
        'sales_admin' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_escalation_level1', 'task_escalation_level2',
            'task_escalation_level3', 'task_dependency_completed',
            'lead_score_tier_change', 'workflow_notification',
        ],

        // Staff Roller: yalnızca kendine ait task bildirimleri
        'operations_staff' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
        ],
        'finance_staff' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
        ],
        'marketing_staff' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
            'workflow_notification',
        ],
        'sales_staff' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
            'lead_score_tier_change',
        ],
        'system_staff' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
        ],

        // Danışman Roller
        'senior' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
            'contract_requested', 'contract_approved', 'contract_rejected',
            'contract_cancelled', 'institution_document_shared',
        ],
        'mentor' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
        ],
    ];

    private const STAFF_ROLES = [
        'operations_staff', 'finance_staff',
        'marketing_staff', 'sales_staff', 'system_staff',
    ];

    private const ADMIN_ROLES = [
        'operations_admin', 'finance_admin',
        'marketing_admin', 'sales_admin',
    ];

    /**
     * Bildirim listesi sorgusuna rol bazlı scope uygular.
     * Katman 1: Kategori filtresi
     * Katman 2: Sahiplik filtresi (user_id)
     */
    public function applyScope(Builder $query, User $user): Builder
    {
        $role      = (string) ($user->role ?? '');
        $userId    = (int) $user->id;
        $companyId = $user->company_id;

        // Şirket izolasyonu — her zaman
        $query->where(function ($q) use ($companyId): void {
            $q->where('company_id', $companyId)->orWhereNull('company_id');
        });

        // Süper roller → şirket bazlı tüm bildirimler
        if (in_array($role, ['manager', 'system_admin'], true)) {
            return $query;
        }

        // Kategori filtresi
        $allowedCategories = self::ROLE_CATEGORY_MAP[$role] ?? [];
        if (empty($allowedCategories)) {
            return $query->whereRaw('1 = 0'); // Tanımsız rol → hiçbir şey
        }

        $query->whereIn('category', $allowedCategories);

        // Sahiplik filtresi
        if (in_array($role, self::STAFF_ROLES, true)) {
            // Staff: yalnızca kendi bildirimleri
            $query->where('user_id', $userId);

        } elseif (in_array($role, self::ADMIN_ROLES, true)) {
            // Admin: kendi + departman kullanıcılarının bildirimleri
            $deptUserIds = $this->getDepartmentUserIds($role, $companyId);
            $query->where(function ($q) use ($userId, $deptUserIds): void {
                $q->where('user_id', $userId)->orWhereIn('user_id', $deptUserIds);
            });

        } elseif ($role === 'senior') {
            // Senior: kendi + sorumlu öğrencilerin bildirimleri
            $studentIds = StudentAssignment::where('senior_email', $user->email)
                ->pluck('student_id')
                ->toArray();
            $query->where(function ($q) use ($userId, $studentIds): void {
                $q->where('user_id', $userId)->orWhereIn('student_id', $studentIds);
            });

        } elseif ($role === 'mentor') {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    private function getDepartmentUserIds(string $adminRole, ?int $companyId): array
    {
        $deptRoles = match ($adminRole) {
            'operations_admin' => ['operations_admin', 'operations_staff'],
            'finance_admin'    => ['finance_admin', 'finance_staff'],
            'marketing_admin'  => ['marketing_admin', 'marketing_staff', 'sales_admin', 'sales_staff'],
            'sales_admin'      => ['sales_admin', 'sales_staff'],
            default            => [],
        };

        if (empty($deptRoles)) {
            return [];
        }

        return User::whereIn('role', $deptRoles)
            ->where('company_id', $companyId)
            ->pluck('id')
            ->toArray();
    }
}
