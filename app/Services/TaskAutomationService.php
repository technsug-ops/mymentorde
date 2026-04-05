<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DmThread;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\ManagerRequest;
use App\Models\MarketingTask;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\TaskChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TaskAutomationService
{
    public function ensureGuestRegistrationReviewTask(GuestApplication $guest): ?MarketingTask
    {
        $assignee = $this->resolveAssignee($guest);
        if (!$assignee) {
            return null;
        }

        return $this->createTaskIfMissing(
            companyId: (int) ($guest->company_id ?: 1),
            sourceType: 'guest_registration_submit',
            sourceId: (string) $guest->id,
            title: 'Guest on kayit incelemesi',
            description: 'Guest #'.$guest->id.' formu gonderdi. Inceleme ve yonlendirme adimi bekleniyor.',
            priority: 'high',
            department: 'operations',
            dueDate: now()->addHours(MarketingTask::defaultSlaHours('high')),
            assigneeUserId: (int) $assignee->id,
            processType: 'guest_intake',
            workflowStage: 'intake_received'
        );
    }

    public function ensureGuestTicketTask(GuestApplication $guest, GuestTicket $ticket): ?MarketingTask
    {
        $assignee = $this->resolveGuestTicketAssignee($guest, $ticket);
        if (!$assignee) {
            return null;
        }

        $priority = (string) ($ticket->priority ?? 'normal');
        if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            $priority = 'normal';
        }

        return $this->createTaskIfMissing(
            companyId: (int) ($guest->company_id ?: 1),
            sourceType: 'guest_ticket_opened',
            sourceId: (string) $ticket->id,
            title: 'Guest ticket yaniti',
            description: 'Ticket #'.$ticket->id.' | dept: '.(string) ($ticket->department ?? 'operations').' | konu: '.(string) ($ticket->subject ?? '-'),
            priority: $priority === 'high' ? 'high' : 'normal',
            department: (string) ($ticket->department ?: 'operations'),
            dueDate: now()->addHours($priority === 'high' ? 12 : 24),
            assigneeUserId: (int) $assignee->id
        );
    }

    public function ensureContractReviewTask(GuestApplication $guest): ?MarketingTask
    {
        $companyId = (int) ($guest->company_id ?: 1);
        $guestName = trim((string) ($guest->full_name ?? $guest->first_name ?? ('Guest #'.$guest->id)));

        // Operations task — sözleşme hazırlama
        $opsAssignee = $this->resolveAssignee($guest);
        $opsTask = null;
        if ($opsAssignee) {
            $opsTask = $this->createTaskIfMissing(
                companyId: $companyId,
                sourceType: 'guest_contract_requested',
                sourceId: (string) $guest->id,
                title: 'Guest sozlesme hazirlama',
                description: 'Guest #'.$guest->id.' sozlesme talebi gonderdi. Sozlesmeyi hazirla ve portale yukle.',
                priority: 'high',
                department: 'operations',
                dueDate: now()->addDays(1),
                assigneeUserId: (int) $opsAssignee->id,
                processType: 'guest_intake',
                workflowStage: 'contract_prep'
            );
        }

        // Marketing/Sales task — ilk satış görüşmesi planlaması
        $salesAssignee = $this->resolveMarketingAssignee($companyId);
        if ($salesAssignee) {
            $this->createTaskIfMissing(
                companyId: $companyId,
                sourceType: 'guest_contract_sales_followup',
                sourceId: (string) $guest->id,
                title: 'Sozlesme talebi - satis gorusmesi',
                description: $guestName.' sozlesme talebi gonderdi. Ilk satis/tanitim gorusmesini planla ve gorusmeyi gerceklestir.',
                priority: 'high',
                department: 'marketing',
                dueDate: now()->addDays(1),
                assigneeUserId: (int) $salesAssignee->id,
                processType: 'guest_intake',
                workflowStage: 'needs_assessment'
            );
        }

        return $opsTask;
    }

    public function ensureSignedContractTask(GuestApplication $guest): ?MarketingTask
    {
        $assignee = $this->resolveAssignee($guest);
        if (!$assignee) {
            return null;
        }

        return $this->createTaskIfMissing(
            companyId: (int) ($guest->company_id ?: 1),
            sourceType: 'guest_contract_signed_uploaded',
            sourceId: (string) $guest->id,
            title: 'Imzali sozlesme onayi',
            description: 'Guest #'.$guest->id.' imzali sozlesme yukledi. Onay adimi bekleniyor.',
            priority: 'urgent',
            department: 'operations',
            dueDate: now()->addHours(MarketingTask::defaultSlaHours('urgent')),
            assigneeUserId: (int) $assignee->id,
            processType: 'guest_intake',
            workflowStage: 'onboarding'
        );
    }

    public function ensureGuestDocumentTask(GuestApplication $guest, Document $document): ?MarketingTask
    {
        $assignee = $this->resolveAssignee($guest);
        if (!$assignee) {
            return null;
        }

        return $this->createTaskIfMissing(
            companyId: (int) ($guest->company_id ?: 1),
            sourceType: 'guest_document_uploaded',
            sourceId: (string) $document->id,
            title: 'Guest belge kontrolu',
            description: $this->buildDocumentTaskDescription('Guest #'.(string) $guest->id, $document),
            priority: 'normal',
            department: 'operations',
            dueDate: now()->addDay(),
            assigneeUserId: (int) $assignee->id,
            processType: 'document_management',
            workflowStage: 'doc_review'
        );
    }

    public function ensureStudentAssignmentTask(StudentAssignment $assignment): ?MarketingTask
    {
        $assignee = $this->resolveStudentAssignee((string) $assignment->student_id, (int) ($assignment->company_id ?: 0));
        if (!$assignee) {
            return null;
        }

        return $this->createTaskIfMissing(
            companyId: (int) ($assignment->company_id ?: 1),
            sourceType: 'student_assignment_upsert',
            sourceId: (string) $assignment->id,
            title: 'Student atama takibi',
            description: 'Student '.(string) $assignment->student_id.' atama kaydi guncellendi.',
            priority: 'normal',
            department: 'advisory',
            dueDate: now()->addDays(1),
            assigneeUserId: (int) $assignee->id,
            relatedStudentId: (string) $assignment->student_id
        );
    }

    public function ensureStudentOutcomeTask(ProcessOutcome $outcome): ?MarketingTask
    {
        $assignment = StudentAssignment::query()
            ->where('student_id', (string) $outcome->student_id)
            ->first();

        $companyId = (int) ($assignment->company_id ?? 1);
        $assignee = $this->resolveStudentAssignee((string) $outcome->student_id, $companyId);
        if (!$assignee) {
            return null;
        }

        $priority = in_array((string) $outcome->outcome_type, ['rejection', 'correction_request'], true)
            ? 'high'
            : 'normal';

        return $this->createTaskIfMissing(
            companyId: $companyId,
            sourceType: 'student_process_outcome_created',
            sourceId: (string) $outcome->id,
            title: 'Student process outcome aksiyonu',
            description: 'Student '.(string) $outcome->student_id.' icin outcome olustu: '.(string) $outcome->process_step.' / '.(string) $outcome->outcome_type,
            priority: $priority,
            department: 'advisory',
            dueDate: now()->addDay(),
            assigneeUserId: (int) $assignee->id,
            processType: 'uni_assist',
            workflowStage: 'ua_result',
            relatedStudentId: (string) $outcome->student_id
        );
    }

    public function ensureStudentDocumentTask(Document $document): ?MarketingTask
    {
        $assignment = StudentAssignment::query()
            ->where('student_id', (string) $document->student_id)
            ->first();

        $companyId = (int) ($assignment->company_id ?? 1);
        $assignee = $this->resolveStudentAssignee((string) $document->student_id, $companyId);
        if (!$assignee) {
            return null;
        }

        return $this->createTaskIfMissing(
            companyId: $companyId,
            sourceType: 'student_document_uploaded',
            sourceId: (string) $document->id,
            title: 'Student belge kontrolu',
            description: $this->buildDocumentTaskDescription('Student '.(string) $document->student_id, $document),
            priority: 'normal',
            department: 'advisory',
            dueDate: now()->addDay(),
            assigneeUserId: (int) $assignee->id,
            processType: 'document_management',
            workflowStage: 'doc_review',
            relatedStudentId: (string) $document->student_id
        );
    }

    public function ensureStudentOnboardingTasks(string $studentId, ?string $seniorEmail = null, int $companyId = 1): int
    {
        $studentId = trim($studentId);
        if ($studentId === '') {
            return 0;
        }

        $assignment = StudentAssignment::query()->where('student_id', $studentId)->first();
        $effectiveCompanyId = (int) ($assignment->company_id ?? $companyId ?: 1);
        $assignee = $this->resolveStudentAssignee($studentId, $effectiveCompanyId);
        if (!$assignee) {
            return 0;
        }

        // Zincirleme bağımlılıkla 3 görev oluştur: kickoff → belge → process
        $count = 0;

        $kickoff = $this->createTaskIfMissing(
            companyId: $effectiveCompanyId,
            sourceType: 'student_onboarding_auto',
            sourceId: $studentId.':kickoff',
            title: 'Onboarding kickoff',
            description: "Student {$studentId} icin onboarding acilis gorusmesini planla.",
            priority: 'high',
            department: 'advisory',
            dueDate: now()->addHours(MarketingTask::defaultSlaHours('high')),
            assigneeUserId: (int) $assignee->id,
            relatedStudentId: $studentId
        );
        $count++;

        $docsReview = $this->createTaskIfMissing(
            companyId: $effectiveCompanyId,
            sourceType: 'student_onboarding_auto',
            sourceId: $studentId.':docs_review',
            title: 'Onboarding belge kontrolu',
            description: "Student {$studentId} icin baslangic belge setini kontrol et.",
            priority: 'normal',
            department: 'advisory',
            dueDate: now()->addDays(2),
            assigneeUserId: (int) $assignee->id,
            dependsOnTaskId: (int) $kickoff->id,
            relatedStudentId: $studentId
        );
        $count++;

        $this->createTaskIfMissing(
            companyId: $effectiveCompanyId,
            sourceType: 'student_onboarding_auto',
            sourceId: $studentId.':first_process_step',
            title: 'Ilk process adimi',
            description: "Student {$studentId} icin ilk process step'i baslat (application_prep).",
            priority: 'normal',
            department: 'advisory',
            dueDate: now()->addDays(3),
            assigneeUserId: (int) $assignee->id,
            dependsOnTaskId: (int) $docsReview->id,
            relatedStudentId: $studentId
        );
        $count++;

        return $count;
    }

    public function ensureManagerRequestTask(ManagerRequest $request): ?MarketingTask
    {
        $managerId = (int) ($request->target_manager_user_id ?? 0);
        if ($managerId <= 0) {
            $managerId = (int) User::query()
                ->when((int) ($request->company_id ?? 0) > 0, fn ($q) => $q->where('company_id', (int) $request->company_id))
                ->where('role', User::ROLE_MANAGER)
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id');
        }
        if ($managerId <= 0) {
            return null;
        }

        $department = match ((string) ($request->request_type ?? 'general')) {
            'finance' => 'finance',
            'operations' => 'operations',
            'approval' => 'advisory',
            'advisory' => 'advisory',
            'system' => 'system',
            'marketing' => 'marketing',
            default => 'operations',
        };

        return $this->createTaskIfMissing(
            companyId: (int) ($request->company_id ?: 1),
            sourceType: 'manager_request_created',
            sourceId: (string) $request->id,
            title: 'Manager talebi: '.(string) ($request->subject ?? 'Talep'),
            description: trim((string) ($request->description ?? '')),
            priority: (string) ($request->priority ?: 'normal'),
            department: $department,
            dueDate: $request->due_date ? Carbon::parse($request->due_date) : now()->addDay(),
            assigneeUserId: $managerId
        );
    }

    public function ensureConversationQuickRequestTask(DmThread $thread, string $source = 'conversation_quick_request'): ?MarketingTask
    {
        $companyId = (int) ($thread->company_id ?: 1);
        $advisorUserId = (int) ($thread->advisor_user_id ?? 0);
        if ($advisorUserId <= 0) {
            return null;
        }

        $title = $thread->thread_type === 'guest'
            ? 'Guest hizli bilgi talebi'
            : 'Student hizli bilgi talebi';

        $participant = $thread->thread_type === 'guest'
            ? ('guest#'.(string) ($thread->guest_application_id ?? '-'))
            : ('student:'.(string) ($thread->student_id ?? '-'));

        return $this->createTaskIfMissing(
            companyId: $companyId,
            sourceType: $source,
            sourceId: (string) $thread->id,
            title: $title,
            description: "DM thread #{$thread->id} icin acil donus talebi var ({$participant}).",
            priority: 'high',
            department: 'advisory',
            dueDate: now()->addHours(12),
            assigneeUserId: $advisorUserId
        );
    }

    public function ensureConversationResponseTask(DmThread $thread, int $slaHours = 24): ?MarketingTask
    {
        $companyId = (int) ($thread->company_id ?: 1);
        $advisorUserId = (int) ($thread->advisor_user_id ?? 0);
        if ($advisorUserId <= 0) {
            return null;
        }

        $sla = $slaHours > 0 ? $slaHours : 24;
        $department = (string) ($thread->department ?: 'advisory');
        if (!in_array($department, ['operations', 'finance', 'advisory', 'marketing', 'system'], true)) {
            $department = 'advisory';
        }

        $priority = $sla <= 12 ? 'high' : 'normal';
        $dueAt = now()->addHours($sla);
        $sourceType = 'conversation_response_due';
        $sourceId = (string) $thread->id;

        $existing = MarketingTask::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('assigned_user_id', $advisorUserId)
            ->where('status', '!=', 'done')
            ->first();

        if ($existing) {
            $existing->forceFill([
                'title' => 'DM yanit bekliyor',
                'description' => 'Thread #'.$sourceId.' icin katilimci mesajina donus bekleniyor.',
                'priority' => $priority,
                'department' => $department,
                'due_date' => $dueAt->toDateString(),
                'status' => 'todo',
                'completed_at' => null,
                'escalate_after_hours' => $sla,
            ])->save();
            return $existing;
        }

        return $this->createTaskIfMissing(
            companyId: $companyId,
            sourceType: $sourceType,
            sourceId: $sourceId,
            title: 'DM yanit bekliyor',
            description: 'Thread #'.$sourceId.' icin katilimci mesajina donus bekleniyor.',
            priority: $priority,
            department: $department,
            dueDate: $dueAt,
            assigneeUserId: $advisorUserId
        );
    }

    public function markConversationResponseDone(DmThread $thread): int
    {
        return $this->markTasksDoneBySource('conversation_response_due', (string) $thread->id);
    }

    private function buildDocumentTaskDescription(string $ownerLabel, Document $document): string
    {
        $categoryCode = trim((string) data_get($document, 'category.code', ''));
        if ($categoryCode === '') {
            $categoryCode = trim((string) $document->category()->value('code'));
        }

        $documentId = trim((string) ($document->document_id ?: 'DOC#'.(string) $document->id));
        $fileName = trim((string) ($document->standard_file_name ?: $document->original_file_name ?: ''));
        $fileName = $fileName !== '' ? Str::limit($fileName, 42, '...') : '';

        $parts = [
            $ownerLabel.' belge yukledi',
            'id:'.$documentId,
            'code:'.($categoryCode !== '' ? $categoryCode : '-'),
        ];
        if ($fileName !== '') {
            $parts[] = 'dosya:'.$fileName;
        }

        return implode(' | ', $parts);
    }

    private function createTaskIfMissing(
        int $companyId,
        string $sourceType,
        string $sourceId,
        string $title,
        string $description,
        string $priority,
        string $department,
        Carbon $dueDate,
        int $assigneeUserId,
        ?int $dependsOnTaskId = null,
        ?string $processType = null,
        ?string $workflowStage = null,
        ?string $relatedStudentId = null
    ): MarketingTask {
        $companyId = $companyId > 0 ? $companyId : 1;
        $existing = MarketingTask::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('assigned_user_id', $assigneeUserId)
            ->where('status', '!=', 'done')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Bağımlılık varsa status blocked olarak başlar
        $status = $dependsOnTaskId ? 'blocked' : 'todo';

        $task = MarketingTask::query()->withoutGlobalScope('company')->create([
            'company_id'           => $companyId,
            'title'                => $title,
            'description'          => $description,
            'status'               => $status,
            'priority'             => $priority,
            'department'           => in_array($department, ['operations', 'finance', 'advisory', 'marketing', 'system'], true) ? $department : 'operations',
            'due_date'             => $dueDate->toDateString(),
            'assigned_user_id'     => $assigneeUserId,
            'created_by_user_id'   => null,
            'depends_on_task_id'   => $dependsOnTaskId,
            'escalate_after_hours' => MarketingTask::defaultSlaHours($priority),
            'is_auto_generated'    => true,
            'source_type'          => $sourceType,
            'source_id'            => $sourceId,
            'process_type'         => $processType,
            'workflow_stage'       => $workflowStage,
            'related_student_id'   => $relatedStudentId,
        ]);

        // Kaynak tipine göre otomatik checklist oluştur
        $checklistItems = config("task_checklists.{$sourceType}", []);
        foreach ($checklistItems as $idx => $label) {
            TaskChecklist::create([
                'task_id'    => $task->id,
                'title'      => $label,
                'sort_order' => $idx,
                'is_done'    => false,
            ]);
        }

        return $task;
    }

    public function markTasksDoneBySource(string $sourceType, string $sourceId): int
    {
        $sourceType = trim($sourceType);
        $sourceId = trim($sourceId);
        if ($sourceType === '' || $sourceId === '') {
            return 0;
        }

        return MarketingTask::query()
            ->withoutGlobalScope('company')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', '!=', 'done')
            ->update([
                'status' => 'done',
                'completed_at' => now(),
            ]);
    }

    public function reassignTasksBySource(string $sourceType, string $sourceId, int $assigneeUserId): int
    {
        $sourceType = trim($sourceType);
        $sourceId = trim($sourceId);
        if ($sourceType === '' || $sourceId === '' || $assigneeUserId <= 0) {
            return 0;
        }

        return MarketingTask::query()
            ->withoutGlobalScope('company')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', '!=', 'done')
            ->update([
                'assigned_user_id' => $assigneeUserId,
                'status' => 'todo',
                'completed_at' => null,
            ]);
    }

    public function reopenTasksBySource(string $sourceType, string $sourceId): int
    {
        $sourceType = trim($sourceType);
        $sourceId = trim($sourceId);
        if ($sourceType === '' || $sourceId === '') {
            return 0;
        }

        return MarketingTask::query()
            ->withoutGlobalScope('company')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'done')
            ->update([
                'status' => 'todo',
                'completed_at' => null,
            ]);
    }

    private function resolveAssignee(GuestApplication $guest): ?User
    {
        $companyId = (int) ($guest->company_id ?: 0);

        $assignedSeniorEmail = strtolower(trim((string) ($guest->assigned_senior_email ?? '')));
        if ($assignedSeniorEmail !== '') {
            $senior = User::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('email', strtolower($assignedSeniorEmail))
                ->where('is_active', true)
                ->first();
            if ($senior) {
                return $senior;
            }
        }

        return User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', [User::ROLE_MANAGER, User::ROLE_OPERATIONS_ADMIN, User::ROLE_SYSTEM_ADMIN])
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN role = 'manager' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    private function resolveGuestTicketAssignee(GuestApplication $guest, GuestTicket $ticket): ?User
    {
        $companyId = (int) ($guest->company_id ?: 0);
        $department = strtolower(trim((string) ($ticket->department ?? 'operations')));

        $assignedUserId = (int) ($ticket->assigned_user_id ?? 0);
        if ($assignedUserId > 0) {
            $assignedUser = User::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('id', $assignedUserId)
                ->where('is_active', true)
                ->first();
            if ($assignedUser) {
                return $assignedUser;
            }
        }

        $roleCandidates = match ($department) {
            'finance' => [User::ROLE_FINANCE_ADMIN, User::ROLE_FINANCE_STAFF, User::ROLE_MANAGER],
            'advisory' => [User::ROLE_SENIOR, User::ROLE_MENTOR, User::ROLE_MANAGER],
            'marketing' => [User::ROLE_MARKETING_ADMIN, User::ROLE_MARKETING_STAFF, User::ROLE_SALES_ADMIN, User::ROLE_SALES_STAFF, User::ROLE_MANAGER],
            'system' => [User::ROLE_SYSTEM_ADMIN, User::ROLE_SYSTEM_STAFF, User::ROLE_MANAGER],
            default => [User::ROLE_OPERATIONS_ADMIN, User::ROLE_OPERATIONS_STAFF, User::ROLE_MANAGER],
        };

        $assignedSeniorEmail = strtolower(trim((string) ($guest->assigned_senior_email ?? '')));
        if ($department === 'advisory' && $assignedSeniorEmail !== '') {
            $senior = User::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('email', strtolower($assignedSeniorEmail))
                ->whereIn('role', [User::ROLE_SENIOR, User::ROLE_MENTOR])
                ->where('is_active', true)
                ->first();
            if ($senior) {
                return $senior;
            }
        }

        return User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', $roleCandidates)
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN role = 'manager' THEN 1 ELSE 0 END")
            ->orderBy('id')
            ->first()
            ?: $this->resolveAssignee($guest);
    }

    private function resolveMarketingAssignee(int $companyId = 0): ?User
    {
        return User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', [
                User::ROLE_MARKETING_ADMIN,
                User::ROLE_SALES_ADMIN,
                User::ROLE_MARKETING_STAFF,
                User::ROLE_SALES_STAFF,
                User::ROLE_MANAGER,
            ])
            ->where('is_active', true)
            ->orderByRaw("CASE
                WHEN role = 'marketing_admin' THEN 0
                WHEN role = 'sales_admin'     THEN 1
                WHEN role = 'marketing_staff' THEN 2
                WHEN role = 'sales_staff'     THEN 3
                ELSE 4
            END")
            ->orderBy('id')
            ->first();
    }

    private function resolveStudentAssignee(string $studentId, int $companyId = 0): ?User
    {
        $studentId = trim($studentId);
        if ($studentId !== '') {
            $assignment = StudentAssignment::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('student_id', $studentId)
                ->first();

            $seniorEmail = strtolower(trim((string) ($assignment->senior_email ?? '')));
            if ($seniorEmail !== '') {
                $senior = User::query()
                    ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                    ->where('email', strtolower($seniorEmail))
                    ->whereIn('role', [User::ROLE_SENIOR, User::ROLE_MENTOR])
                    ->where('is_active', true)
                    ->first();

                if ($senior) {
                    return $senior;
                }
            }
        }

        return User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', [User::ROLE_MANAGER, User::ROLE_OPERATIONS_ADMIN, User::ROLE_SYSTEM_ADMIN])
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN role = 'manager' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }
}
