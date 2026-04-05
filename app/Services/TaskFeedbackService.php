<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\MarketingTask;
use App\Models\NotificationDispatch;
use App\Models\StudentAssignment;
use App\Models\User;

/**
 * Task tamamlandığında (status → done) devreye girer.
 * İlgili senior'ı tespit eder, bildirim oluşturur ve GuestApplication.last_senior_action_at günceller.
 */
class TaskFeedbackService
{
    /**
     * process_type'ı olan bir task done'a geçtiğinde çağrılır.
     */
    public function onTaskDone(MarketingTask $task): void
    {
        // Sadece CRM süreç task'larını işle
        if (! $task->process_type) {
            return;
        }

        [$studentId, $seniorEmail] = $this->resolveStudentAndSenior($task);

        if (! $seniorEmail) {
            return;
        }

        $senior = User::query()->where('email', $seniorEmail)->first();
        if (! $senior) {
            return;
        }

        $processLabel = MarketingTask::PROCESS_TYPES[$task->process_type] ?? $task->process_type;

        // Senior'a in-app bildirim oluştur
        NotificationDispatch::create([
            'company_id'  => $task->company_id,
            'user_id'     => $senior->id,
            'channel'     => 'in_app',
            'category'    => 'task_completed',
            'subject'     => 'Görev Tamamlandı — ' . $processLabel,
            'body'        => '"' . $task->title . '" görevi tamamlandı. Öğrenci süreci bir adım ilerledi.',
            'status'      => 'sent',
            'student_id'  => $studentId,
            'source_type' => 'marketing_task',
            'source_id'   => (string) $task->id,
            'triggered_by' => 'system',
        ]);

        // Öğrencinin GuestApplication.last_senior_action_at güncelle
        if ($studentId) {
            GuestApplication::query()
                ->where('converted_student_id', $studentId)
                ->whereNull('deleted_at')
                ->update(['last_senior_action_at' => now()]);
        }
    }

    /**
     * Task'tan student_id + senior_email çözer.
     * Önce related_student_id'e bakar, yoksa source_type/source_id üzerinden çözer.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveStudentAndSenior(MarketingTask $task): array
    {
        $studentId = $task->related_student_id ? (string) $task->related_student_id : null;

        // related_student_id varsa doğrudan StudentAssignment'dan senior bul
        if ($studentId) {
            $seniorEmail = $this->seniorEmailForStudent($studentId);
            if ($seniorEmail) {
                return [$studentId, $seniorEmail];
            }
        }

        // Guest-kaynaklı task'lar — source_id = guest_application.id
        $guestSourceTypes = [
            'guest_registration_submit',
            'guest_contract_requested',
            'guest_contract_sales_followup',
            'guest_contract_signed_uploaded',
            'guest_document_uploaded',
        ];

        if ($task->source_type && in_array($task->source_type, $guestSourceTypes, true)) {
            $guest = GuestApplication::query()->find($task->source_id);
            if ($guest) {
                $seniorEmail = (string) ($guest->assigned_senior_email ?? '');
                $resolvedStudentId = $studentId ?? ((string) ($guest->converted_student_id ?? '')) ?: null;
                if ($seniorEmail !== '') {
                    return [$resolvedStudentId, $seniorEmail];
                }
            }
        }

        return [null, null];
    }

    private function seniorEmailForStudent(string $studentId): ?string
    {
        return StudentAssignment::query()
            ->where('student_id', $studentId)
            ->latest('id')
            ->value('senior_email');
    }
}
