<?php

namespace App\Http\Controllers\Student\Concerns;

use App\Http\Controllers\Concerns\UsesRequiredDocuments;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\NotificationDispatch;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentRevenue;
use App\Services\StudentGuestResolver;
use App\Support\SchemaCache;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait StudentPortalTrait
{
    use UsesRequiredDocuments;

    protected function baseData(Request $request, string $moduleKey, string $title, string $description): array
    {
        $user      = $request->user();
        $studentId = trim((string) ($user->student_id ?? ''));

        $guestApplication = app(StudentGuestResolver::class)->resolveForUser($user);
        if ($studentId === '') {
            $studentId = (string) ($guestApplication?->converted_student_id ?? '');
        }

        $assignment        = null;
        $revenue           = null;
        $docSummary        = ['total' => 0, 'approved' => 0, 'uploaded' => 0, 'rejected' => 0, 'required_done' => 0, 'required_total' => 0];
        $notificationCount = 0;
        $progressPercent   = 0;

        if ($studentId !== '') {
            $assignment = Cache::remember("student_assignment_{$studentId}", 300, fn () =>
                StudentAssignment::query()->where('student_id', $studentId)->first()
            );
            $revenue = Cache::remember("student_revenue_{$studentId}", 300, fn () =>
                StudentRevenue::query()->where('student_id', $studentId)->first()
            );

            $ownerIds = $guestApplication
                ? $this->resolveDocumentOwnerIds($guestApplication)
                : collect([$studentId])->filter()->values();

            $ownerKey  = implode('_', $ownerIds->sort()->values()->all());
            $documents = Cache::remember("student_docs_{$ownerKey}", 60, fn () =>
                Document::query()
                    ->whereIn('student_id', $ownerIds)
                    ->with('category:id,code')
                    ->get(['id', 'status', 'category_id'])
            );

            $requiredTotal = 0;
            $requiredDone  = 0;
            if ($guestApplication) {
                $checklist = collect($this->requiredDocumentsByApplicationType(
                    (string) ($guestApplication->application_type ?? 'bachelor'),
                    $this->uploadedCategoryCodes($guestApplication),
                    'student'
                ));
                $requiredTotal = (int) $checklist->filter(fn (array $i) => (bool) ($i['is_required'] ?? false))->count();
                $requiredDone  = (int) $checklist->filter(fn (array $i) => (bool) ($i['is_required'] ?? false) && (bool) ($i['uploaded'] ?? false))->count();
            }

            $outcomeCount      = (int) Cache::remember("student_outcome_cnt_{$studentId}", 60, fn () =>
                ProcessOutcome::query()->where('student_id', $studentId)->count()
            );
            $notificationCount = (int) Cache::remember("student_notif_cnt_{$studentId}", 60, fn () =>
                NotificationDispatch::query()->where('student_id', $studentId)->count()
            );

            $docSummary = [
                'total'          => (int) $documents->count(),
                'approved'       => (int) $documents->where('status', 'approved')->count(),
                'uploaded'       => (int) $documents->where('status', 'uploaded')->count(),
                'rejected'       => (int) $documents->where('status', 'rejected')->count(),
                'required_done'  => $requiredDone,
                'required_total' => $requiredTotal,
            ];

            $scoreParts      = [
                $requiredTotal > 0 ? (int) round(($requiredDone / $requiredTotal) * 100) : 0,
                $outcomeCount > 0 ? 100 : 0,
                $notificationCount > 0 ? 100 : 0,
            ];
            $progressPercent = (int) round(array_sum($scoreParts) / count($scoreParts));
        }

        return [
            'moduleKey'         => $moduleKey,
            'title'             => $title,
            'description'       => $description,
            'studentId'         => $studentId,
            'user'              => $user,
            'guestApplication'  => $guestApplication,
            'guest'             => $guestApplication,
            'assignment'        => $assignment,
            'revenue'           => $revenue,
            'docSummary'        => $docSummary,
            'notificationCount' => $notificationCount,
            'progressPercent'   => $progressPercent,
        ];
    }

    protected function resolveStudentGuest(Request $request): ?GuestApplication
    {
        return app(StudentGuestResolver::class)->resolveForUser($request->user());
    }

    protected function resolveDocumentOwnerId(GuestApplication $guest): string
    {
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId !== '') {
            return $studentId;
        }
        return 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
    }

    protected function resolveDocumentOwnerIds(GuestApplication $guest): Collection
    {
        $ids = collect([$this->resolveDocumentOwnerId($guest)]);
        $ids->push('GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT));

        return $ids->map(fn ($v) => trim((string) $v))->filter()->unique()->values();
    }

    protected function uploadedCategoryCodes(GuestApplication $guest): array
    {
        $ownerIds = $this->resolveDocumentOwnerIds($guest);
        return Document::query()
            ->whereIn('student_id', $ownerIds)
            ->whereIn('status', ['uploaded', 'approved'])
            ->with('category:id,code')
            ->get()
            ->map(fn (Document $d) => (string) ($d->category->code ?? ''))
            ->filter()
            ->values()
            ->all();
    }
}
