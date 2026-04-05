<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Senior\Concerns\SeniorPortalTrait;
use App\Models\AccountVault;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\InternalNote;
use App\Models\ProcessOutcome;
use App\Models\StudentAccommodation;
use App\Models\StudentAppointment;
use App\Models\StudentAssignment;
use App\Models\StudentInstitutionDocument;
use App\Models\StudentUniversityApplication;
use App\Models\StudentVisaApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SeniorStudentController extends Controller
{
    use SeniorPortalTrait;

    // ── Öğrenci Listesi ──────────────────────────────────────────────────────

    public function students(Request $request)
    {
        $email    = $this->seniorEmail($request);
        $q        = trim((string) $request->query('q', ''));
        $archived = trim((string) $request->query('archived', 'all'));
        $risk     = trim((string) $request->query('risk', 'all'));

        $assignmentQuery = StudentAssignment::query()
            ->whereRaw('lower(senior_email) = ?', [$email]);
        if ($q !== '') {
            $assignmentQuery->where(function ($w) use ($q) {
                $w->where('student_id', 'like', "%{$q}%")
                    ->orWhere('branch', 'like', "%{$q}%")
                    ->orWhere('dealer_id', 'like', "%{$q}%");
            });
        }
        if ($archived === 'yes') {
            $assignmentQuery->where('is_archived', true);
        } elseif ($archived === 'no') {
            $assignmentQuery->where('is_archived', false);
        }
        if ($risk !== '' && $risk !== 'all') {
            $assignmentQuery->where('risk_level', $risk);
        }

        $assignments = $assignmentQuery
            ->latest('updated_at')
            ->limit(1000)
            ->get(['student_id', 'branch', 'dealer_id', 'risk_level', 'payment_status', 'is_archived', 'updated_at']);

        $guestPoolQuery = GuestApplication::query()
            ->where('assigned_senior_email', $email)
            ->latest('updated_at');
        if ($q !== '') {
            $guestPoolQuery->where(function ($w) use ($q) {
                $w->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('application_type', 'like', "%{$q}%");
            });
        }
        $guestPool = $guestPoolQuery->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'application_type', 'created_at']);

        return view('senior.students', [
            'assignments'  => $assignments,
            'guestPool'    => $guestPool,
            'filters'      => compact('q', 'archived', 'risk'),
            'sidebarStats' => $this->sidebarStats($request),
        ]);
    }

    // ── CSV Export ───────────────────────────────────────────────────────────

    public function studentsExportCsv(Request $request): Response
    {
        $email    = $this->seniorEmail($request);
        $q        = trim((string) $request->query('q', ''));
        $archived = trim((string) $request->query('archived', 'all'));
        $risk     = trim((string) $request->query('risk', 'all'));

        $query = StudentAssignment::query()->whereRaw('lower(senior_email) = ?', [$email]);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('student_id', 'like', "%{$q}%")
                    ->orWhere('branch', 'like', "%{$q}%")
                    ->orWhere('dealer_id', 'like', "%{$q}%");
            });
        }
        if ($archived === 'yes') { $query->where('is_archived', true); }
        elseif ($archived === 'no') { $query->where('is_archived', false); }
        if ($risk !== '' && $risk !== 'all') { $query->where('risk_level', $risk); }

        $rows = $query->latest('updated_at')
            ->get(['student_id', 'branch', 'dealer_id', 'risk_level', 'payment_status', 'is_archived', 'updated_at']);

        $csv = implode(',', ['Student ID', 'Şube', 'Dealer', 'Risk', 'Ödeme', 'Arşiv', 'Güncellendi']) . "\n";
        foreach ($rows as $s) {
            $csv .= implode(',', array_map(
                fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"',
                [
                    $s->student_id ?? '', $s->branch ?? '', $s->dealer_id ?? '',
                    $s->risk_level ?? '', $s->payment_status ?? '',
                    $s->is_archived ? 'Evet' : 'Hayır',
                    optional($s->updated_at)->format('d.m.Y H:i') ?? '',
                ]
            )) . "\n";
        }

        $filename = 'ogrencilerim_' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ── Öğrenci 360° Detay ──────────────────────────────────────────────────

    public function studentDetail(Request $request, string $studentId)
    {
        $assignedIds = $this->assignedStudentIds($request);
        abort_if(!$assignedIds->contains($studentId), 403, 'Bu öğrenci size atanmamış.');

        $assignment = StudentAssignment::query()
            ->whereRaw('lower(senior_email) = ?', [$this->seniorEmail($request)])
            ->where('student_id', $studentId)
            ->first();

        $guest = GuestApplication::query()
            ->where('converted_student_id', $studentId)
            ->where('converted_to_student', true)
            ->first();

        $documents = Document::query()
            ->where('student_id', $studentId)
            ->with('category')
            ->latest()
            ->limit(50)
            ->get();

        $outcomes = ProcessOutcome::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(30)
            ->get();

        $appointments = StudentAppointment::query()
            ->where('student_id', $studentId)
            ->orderByDesc('scheduled_at')
            ->limit(20)
            ->get();

        $tickets = GuestTicket::query()
            ->where('guest_application_id', optional($guest)->id)
            ->latest()
            ->limit(20)
            ->get(['id', 'subject', 'status', 'priority', 'created_at', 'department']);

        $vaults = AccountVault::query()
            ->where('student_id', $studentId)
            ->get(['id', 'service_label', 'account_username', 'is_visible_to_student', 'created_at']);

        $notes = InternalNote::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(20)
            ->get(['id', 'category', 'priority', 'is_pinned', 'content', 'created_at']);

        $uniApps = StudentUniversityApplication::query()
            ->where('student_id', $studentId)
            ->orderBy('priority')
            ->get();

        $instDocs = StudentInstitutionDocument::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(20)
            ->get();

        $visa = StudentVisaApplication::query()
            ->where('student_id', $studentId)
            ->latest('id')
            ->first();

        $accommodation = StudentAccommodation::query()
            ->where('student_id', $studentId)
            ->latest('id')
            ->first();

        $progress = $this->calculateStudentProgress(
            $studentId, $guest, $documents, $outcomes, $uniApps, $visa, $accommodation
        );

        return view('senior.student-detail', [
            'studentId'     => $studentId,
            'assignment'    => $assignment,
            'guest'         => $guest,
            'documents'     => $documents,
            'outcomes'      => $outcomes,
            'appointments'  => $appointments,
            'tickets'       => $tickets,
            'vaults'        => $vaults,
            'notes'         => $notes,
            'uniApps'       => $uniApps,
            'instDocs'      => $instDocs,
            'visa'          => $visa,
            'accommodation' => $accommodation,
            'progress'      => $progress,
            'sidebarStats'  => $this->sidebarStats($request),
        ]);
    }

    // ── Toplu Belge İnceleme ─────────────────────────────────────────────────

    public function batchReview(Request $request)
    {
        $assignedIds  = $this->assignedStudentIds($request);
        $statusFilter = trim((string) $request->query('status', 'uploaded'));

        $docPaginator = $assignedIds->isEmpty()
            ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50)
            : Document::query()
                ->whereIn('student_id', $assignedIds->all())
                ->where('status', $statusFilter === 'all' ? '!=' : '=', $statusFilter === 'all' ? 'nonexistent' : $statusFilter)
                ->with('category')
                ->latest()
                ->paginate(50)
                ->withQueryString();

        $grouped = $docPaginator->getCollection()->groupBy('student_id');

        $stats = [
            'uploaded' => $assignedIds->isEmpty() ? 0 : Document::whereIn('student_id', $assignedIds->all())->where('status', 'uploaded')->count(),
            'approved' => $assignedIds->isEmpty() ? 0 : Document::whereIn('student_id', $assignedIds->all())->where('status', 'approved')->count(),
            'rejected' => $assignedIds->isEmpty() ? 0 : Document::whereIn('student_id', $assignedIds->all())->where('status', 'rejected')->count(),
        ];

        return view('senior.batch-review', [
            'grouped'      => $grouped,
            'docPaginator' => $docPaginator,
            'statusFilter' => $statusFilter,
            'stats'        => $stats,
            'sidebarStats' => $this->sidebarStats($request),
        ]);
    }

    public function batchReviewAction(Request $request, Document $document): \Illuminate\Http\JsonResponse
    {
        $assignedIds = $this->assignedStudentIds($request);
        abort_if(!$assignedIds->contains((string) $document->student_id), 403);

        $data = $request->validate([
            'action' => ['required', 'in:approve,reject,note'],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        match ($data['action']) {
            'approve' => $document->update([
                'status'      => 'approved',
                'approved_by' => $user?->email,
                'approved_at' => now(),
                'review_note' => $data['note'] ?? null,
            ]),
            'reject' => $document->update([
                'status'      => 'rejected',
                'review_note' => $data['note'] ?? null,
            ]),
            'note' => $document->update([
                'review_note' => $data['note'] ?? null,
            ]),
            default => null,
        };

        return response()->json([
            'ok'     => true,
            'status' => $document->fresh()?->status,
        ]);
    }
}
