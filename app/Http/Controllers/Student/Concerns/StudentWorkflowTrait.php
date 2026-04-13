<?php

namespace App\Http\Controllers\Student\Concerns;

use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\GuestRequiredDocument;
use App\Models\MarketingTask;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\StudentGuestResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait StudentWorkflowTrait
{
    protected function resolveStudentGuest(Request $request): ?GuestApplication
    {
        return app(StudentGuestResolver::class)->resolveForUser($request->user());
    }

    protected function suggestDepartment(string $subject, string $message): string
    {
        $text = strtolower(trim($subject . ' ' . $message));
        if ($text === '') {
            return 'advisory';
        }
        if (str_contains($text, 'fatura') || str_contains($text, 'odeme') || str_contains($text, 'taksit')) {
            return 'finance';
        }
        if (str_contains($text, 'randevu') || str_contains($text, 'evrak') || str_contains($text, 'surec')) {
            return 'operations';
        }
        if (str_contains($text, 'kampanya') || str_contains($text, 'marketing')) {
            return 'marketing';
        }
        if (str_contains($text, 'hata') || str_contains($text, 'sistem') || str_contains($text, 'bug')) {
            return 'system';
        }

        return 'advisory';
    }

    protected function resolveDocumentOwnerId(GuestApplication $guest): string
    {
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId !== '') {
            return $studentId;
        }
        return 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
    }

    protected function resolveDocumentOwnerIds(GuestApplication $guest): \Illuminate\Support\Collection
    {
        $ids = collect([$this->resolveDocumentOwnerId($guest)]);
        $ids->push('GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT));

        return $ids
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values();
    }

    protected function computeDocsReady(GuestApplication $guest, string $ownerId): bool
    {
        $applicationType = trim((string) ($guest->application_type ?? ''));
        if ($applicationType === '') {
            return false;
        }
        $requiredCodes = GuestRequiredDocument::query()
            ->where('application_type', $applicationType)
            ->where('is_active', true)
            ->where('is_required', true)
            ->where('stage', 'guest')
            ->pluck('category_code')
            ->map(fn ($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->unique()
            ->values();
        if ($requiredCodes->isEmpty()) {
            return false;
        }
        $uploadedCodes = Document::query()
            ->whereIn('student_id', $this->resolveDocumentOwnerIds($guest))
            ->whereIn('status', ['uploaded', 'approved'])
            ->with('category:id,code')
            ->get()
            ->map(fn (Document $d) => strtoupper(trim((string) ($d->category->code ?? ''))))
            ->filter()
            ->unique()
            ->values();

        return $requiredCodes->diff($uploadedCodes)->isEmpty();
    }

    protected function createStudentServiceTask(GuestApplication $guest, string $title, string $description, string $priority = 'normal'): ?MarketingTask
    {
        $companyId = (int) ($guest->company_id ?: (app()->bound('current_company_id') ? (int) app('current_company_id') : 1));
        if ($companyId <= 0) {
            $companyId = 1;
        }

        $assigneeUserId = 0;
        $studentId      = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId !== '') {
            $assignment  = StudentAssignment::query()->where('student_id', $studentId)->first();
            $seniorEmail = strtolower(trim((string) ($assignment->senior_email ?? '')));
            if ($seniorEmail !== '') {
                $assigneeUserId = (int) User::query()
                    ->where('email', strtolower($seniorEmail))
                    ->where('is_active', true)
                    ->value('id');
            }
        }

        if ($assigneeUserId <= 0) {
            $assigneeUserId = (int) User::query()
                ->where('company_id', $companyId)
                ->where('role', User::ROLE_MANAGER)
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id');
        }

        if ($assigneeUserId <= 0) {
            return null;
        }

        return MarketingTask::query()->create([
            'company_id'           => $companyId,
            'title'                => trim($title) !== '' ? trim($title) : 'Student servis aksiyonu',
            'description'          => trim($description),
            'status'               => 'todo',
            'priority'             => in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal',
            'department'           => 'advisory',
            'due_date'             => now()->addDay()->toDateString(),
            'assigned_user_id'     => $assigneeUserId,
            'created_by_user_id'   => null,
            'source_type'          => 'student_service_update',
            'source_id'            => (string) $guest->id . ':' . now()->format('YmdHis') . ':' . substr((string) Str::uuid(), 0, 8),
            'is_auto_generated'    => true,
            'escalate_after_hours' => 24,
        ]);
    }

    protected function isServiceSelectionLockedByContract(GuestApplication $guest): bool
    {
        $status = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        return in_array($status, ['requested', 'signed_uploaded', 'approved'], true);
    }

    protected function normalizeContractStatus(string $status): string
    {
        $normalized = strtolower(trim($status));
        return $normalized !== '' ? $normalized : 'not_requested';
    }

    protected function canStudentRequestContractAddendum(GuestApplication $guest): bool
    {
        $status = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        return in_array($status, ['requested', 'signed_uploaded', 'rejected'], true);
    }

    protected function contractStateHasInconsistency(GuestApplication $guest, ?string $normalizedStatus = null): bool
    {
        $status        = $normalizedStatus ?: $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        $hasSnapshot   = trim((string) ($guest->contract_snapshot_text ?? '')) !== '';
        $hasTemplate   = trim((string) ($guest->contract_template_code ?? '')) !== '' || (int) ($guest->contract_template_id ?? 0) > 0;
        $hasSignedFile = trim((string) ($guest->contract_signed_file_path ?? '')) !== '';
        $hasRequestedAt = ! empty($guest->contract_requested_at);
        $hasSignedAt    = ! empty($guest->contract_signed_at);
        $hasApprovedAt  = ! empty($guest->contract_approved_at);

        if (in_array($status, ['requested', 'signed_uploaded', 'approved', 'rejected'], true) && (! $hasSnapshot || ! $hasTemplate)) {
            return true;
        }
        if (in_array($status, ['requested', 'signed_uploaded', 'approved', 'rejected'], true) && ! $hasRequestedAt) {
            return true;
        }
        if (in_array($status, ['signed_uploaded', 'approved'], true) && ! $hasSignedFile) {
            return true;
        }
        if (in_array($status, ['signed_uploaded', 'approved'], true) && ! $hasSignedAt) {
            return true;
        }
        if ($status === 'approved' && ! $hasApprovedAt) {
            return true;
        }
        if ($status === 'not_requested' && ($hasSnapshot || $hasTemplate || $hasSignedFile || $hasRequestedAt || $hasSignedAt || $hasApprovedAt)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,string>
     */
    protected function conditionalRequiredErrors(array $payload): array
    {
        $rules = [
            'passport_number' => [
                'when_key'    => 'has_passport',
                'when_values' => ['yes'],
                'message'     => 'Pasaport varsa seri numarasi zorunludur.',
            ],
            'german_course_name' => [
                'when_key'    => 'is_enrolled_german_course',
                'when_values' => ['yes'],
                'message'     => 'Almanca kurs adi zorunludur.',
            ],
            'teacher_reference_contact' => [
                'when_key'    => 'has_teacher_reference',
                'when_values' => ['yes'],
                'message'     => 'Referans iletisim bilgisi zorunludur.',
            ],
            'germany_stay_date_range' => [
                'when_key'    => 'lived_in_germany_before',
                'when_values' => ['yes'],
                'message'     => 'Almanya tarih araligi zorunludur.',
            ],
            'germany_last_residences' => [
                'when_key'    => 'lived_in_germany_before',
                'when_values' => ['yes'],
                'message'     => 'Almanya ikamet bilgisi zorunludur.',
            ],
            'children_count' => [
                'when_key'    => 'has_children',
                'when_values' => ['yes'],
                'message'     => 'Çocuğunuz varsa kaç çocuğunuz olduğunu belirtin.',
            ],
        ];

        $errors = [];
        // children_count yalnızca evli + çocuk var ise zorunlu
        $isMarried = strtolower(trim((string) ($payload['marital_status'] ?? ''))) === 'married';
        foreach ($rules as $field => $cfg) {
            if ($field === 'children_count' && ! $isMarried) {
                continue;
            }
            $when    = strtolower(trim((string) ($payload[$cfg['when_key']] ?? '')));
            $expects = array_map(static fn ($v) => strtolower(trim((string) $v)), (array) ($cfg['when_values'] ?? []));
            if (! in_array($when, $expects, true)) {
                continue;
            }
            $val = trim((string) ($payload[$field] ?? ''));
            if ($val === '') {
                $errors[$field] = (string) ($cfg['message'] ?? 'Bu alan zorunludur.');
            }
        }

        return $errors;
    }
}
