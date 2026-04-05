<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\MarketingTask;
use App\Models\StudentAccommodation;
use App\Models\StudentAppointment;
use App\Models\StudentAssignment;
use App\Models\StudentVisaApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SeniorVisaHousingController extends Controller
{
    private function seniorEmail(Request $request): string
    {
        return strtolower((string) ($request->user()?->email ?? ''));
    }

    private function assignedStudentIds(Request $request): Collection
    {
        $email     = $this->seniorEmail($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);
        return StudentAssignment::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereRaw('lower(senior_email) = ?', [$email])
            ->pluck('student_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function sidebarStats(Request $request): array
    {
        $email     = $this->seniorEmail($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);
        $base = StudentAssignment::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereRaw('lower(senior_email) = ?', [$email]);
        $studentIds = (clone $base)->pluck('student_id')->filter()->unique();
        $today = now()->toDateString();

        return [
            'active_students' => (int) (clone $base)->where('is_archived', false)->count(),
            'pending_guests' => (int) GuestApplication::query()
                ->whereIn('converted_student_id', $studentIds->all())
                ->where('converted_to_student', false)
                ->count(),
            'today_tasks' => (int) MarketingTask::query()
                ->where('assigned_user_id', (int) optional($request->user())->id)
                ->whereNotIn('status', ['done', 'cancelled'])
                ->whereDate('due_date', $today)
                ->count(),
            'today_appointments' => (int) StudentAppointment::query()
                ->whereRaw('lower(senior_email) = ?', [$email])
                ->whereDate('scheduled_at', $today)
                ->count(),
        ];
    }

    public function visaList(Request $request)
    {
        $assignedIds = $this->assignedStudentIds($request)->toArray();
        $filterStudent = trim((string) $request->query('student_id', ''));
        $filterStatus  = trim((string) $request->query('status', ''));

        $query = StudentVisaApplication::query()
            ->whereIn('student_id', $assignedIds)
            ->latest('id');

        if ($filterStudent !== '') {
            $query->where('student_id', $filterStudent);
        }
        if ($filterStatus !== '') {
            $query->where('status', $filterStatus);
        }

        $visas = $query->paginate(30);

        $assignments = StudentAssignment::query()
            ->whereIn('student_id', $assignedIds)
            ->get(['student_id', 'student_name']);

        return view('senior.visa', [
            'visas'          => $visas,
            'assignments'    => $assignments,
            'assignedIds'    => $assignedIds,
            'filterStudent'  => $filterStudent,
            'filterStatus'   => $filterStatus,
            'statusLabels'   => StudentVisaApplication::STATUS_LABELS,
            'visaTypeLabels' => StudentVisaApplication::VISA_TYPE_LABELS,
            'documentLabels' => StudentVisaApplication::COMMON_DOCUMENTS,
            'sidebarStats'   => $this->sidebarStats($request),
        ]);
    }

    public function visaStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $assignedIds = $this->assignedStudentIds($request)->toArray();

        $data = $request->validate([
            'student_id'           => ['required', 'string', 'in:' . implode(',', $assignedIds)],
            'visa_type'            => ['required', 'in:national_d,student_visa,language_course,other'],
            'status'               => ['required', 'in:not_started,preparing,submitted,in_review,approved,rejected,expired'],
            'consulate_city'       => ['nullable', 'string', 'max:100'],
            'application_date'     => ['nullable', 'date'],
            'appointment_date'     => ['nullable', 'date'],
            'decision_date'        => ['nullable', 'date'],
            'valid_from'           => ['nullable', 'date'],
            'valid_until'          => ['nullable', 'date'],
            'visa_number'          => ['nullable', 'string', 'max:64'],
            'submitted_documents'  => ['nullable', 'array'],
            'submitted_documents.*'=> ['string'],
            'notes'                => ['nullable', 'string', 'max:2000'],
            'rejection_reason'     => ['nullable', 'string', 'max:2000'],
            'is_visible_to_student'=> ['nullable', 'boolean'],
        ]);

        $companyId = app()->bound('current_company_id') ? (string) app('current_company_id') : '';

        StudentVisaApplication::create(array_merge($data, [
            'company_id' => $companyId,
            'added_by'   => $request->user()->id,
            'is_visible_to_student' => (bool) ($data['is_visible_to_student'] ?? true),
        ]));

        return back()->with('status', 'Vize kaydı eklendi.');
    }

    public function visaUpdate(Request $request, StudentVisaApplication $visa): \Illuminate\Http\RedirectResponse
    {
        abort_if(!$this->assignedStudentIds($request)->contains($visa->student_id), 403);

        $data = $request->validate([
            'status'               => ['required', 'in:not_started,preparing,submitted,in_review,approved,rejected,expired'],
            'consulate_city'       => ['nullable', 'string', 'max:100'],
            'appointment_date'     => ['nullable', 'date'],
            'decision_date'        => ['nullable', 'date'],
            'valid_from'           => ['nullable', 'date'],
            'valid_until'          => ['nullable', 'date'],
            'visa_number'          => ['nullable', 'string', 'max:64'],
            'submitted_documents'  => ['nullable', 'array'],
            'submitted_documents.*'=> ['string'],
            'notes'                => ['nullable', 'string', 'max:2000'],
            'rejection_reason'     => ['nullable', 'string', 'max:2000'],
            'is_visible_to_student'=> ['nullable', 'boolean'],
        ]);

        $data['is_visible_to_student'] = (bool) ($data['is_visible_to_student'] ?? $visa->is_visible_to_student);
        $visa->update($data);

        return back()->with('status', 'Vize kaydı güncellendi.');
    }

    public function visaDelete(Request $request, StudentVisaApplication $visa): \Illuminate\Http\RedirectResponse
    {
        abort_if(!$this->assignedStudentIds($request)->contains($visa->student_id), 403);
        $visa->delete();
        return back()->with('status', 'Vize kaydı silindi.');
    }

    public function visaToggleVisibility(Request $request, StudentVisaApplication $visa): \Illuminate\Http\RedirectResponse
    {
        abort_if(!$this->assignedStudentIds($request)->contains($visa->student_id), 403);
        $visa->update(['is_visible_to_student' => !$visa->is_visible_to_student]);
        return back()->with('status', $visa->is_visible_to_student ? 'Öğrenciye görünür yapıldı.' : 'Öğrenci görünürlüğü kapatıldı.');
    }

    // ── Konut Takibi ──────────────────────────────────────────────────────────

    public function housingList(Request $request)
    {
        $assignedIds   = $this->assignedStudentIds($request)->toArray();
        $filterStudent = trim((string) $request->query('student_id', ''));
        $filterStatus  = trim((string) $request->query('status', ''));

        $query = StudentAccommodation::query()
            ->whereIn('student_id', $assignedIds)
            ->latest('id');

        if ($filterStudent !== '') {
            $query->where('student_id', $filterStudent);
        }
        if ($filterStatus !== '') {
            $query->where('booking_status', $filterStatus);
        }

        $accommodations = $query->paginate(30);

        $assignments = StudentAssignment::query()
            ->whereIn('student_id', $assignedIds)
            ->get(['student_id', 'student_name']);

        return view('senior.housing', [
            'accommodations' => $accommodations,
            'assignments'    => $assignments,
            'assignedIds'    => $assignedIds,
            'filterStudent'  => $filterStudent,
            'filterStatus'   => $filterStatus,
            'typeLabels'     => StudentAccommodation::TYPE_LABELS,
            'statusLabels'   => StudentAccommodation::STATUS_LABELS,
            'sidebarStats'   => $this->sidebarStats($request),
        ]);
    }

    public function housingStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $assignedIds = $this->assignedStudentIds($request)->toArray();

        $data = $request->validate([
            'student_id'             => ['required', 'string', 'in:' . implode(',', $assignedIds)],
            'type'                   => ['required', 'in:on_campus,off_campus,host_family,other'],
            'booking_status'         => ['required', 'in:searching,applied,booked,confirmed,cancelled'],
            'address'                => ['nullable', 'string', 'max:255'],
            'city'                   => ['nullable', 'string', 'max:100'],
            'postal_code'            => ['nullable', 'string', 'max:20'],
            'monthly_cost_eur'       => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'utilities_included'     => ['nullable', 'boolean'],
            'move_in_date'           => ['nullable', 'date'],
            'contract_end_date'      => ['nullable', 'date'],
            'landlord_name'          => ['nullable', 'string', 'max:150'],
            'landlord_phone'         => ['nullable', 'string', 'max:30'],
            'landlord_email'         => ['nullable', 'email', 'max:150'],
            'notes'                  => ['nullable', 'string', 'max:2000'],
            'is_visible_to_student'  => ['nullable', 'boolean'],
        ]);

        $companyId = app()->bound('current_company_id') ? (string) app('current_company_id') : '';

        StudentAccommodation::create(array_merge($data, [
            'company_id'            => $companyId,
            'added_by'              => $request->user()->id,
            'utilities_included'    => (bool) ($data['utilities_included'] ?? false),
            'is_visible_to_student' => (bool) ($data['is_visible_to_student'] ?? true),
        ]));

        return back()->with('status', 'Konut kaydı eklendi.');
    }

    public function housingUpdate(Request $request, StudentAccommodation $accommodation): \Illuminate\Http\RedirectResponse
    {
        abort_if(!$this->assignedStudentIds($request)->contains($accommodation->student_id), 403);

        $data = $request->validate([
            'booking_status'         => ['required', 'in:searching,applied,booked,confirmed,cancelled'],
            'address'                => ['nullable', 'string', 'max:255'],
            'city'                   => ['nullable', 'string', 'max:100'],
            'postal_code'            => ['nullable', 'string', 'max:20'],
            'monthly_cost_eur'       => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'utilities_included'     => ['nullable', 'boolean'],
            'move_in_date'           => ['nullable', 'date'],
            'contract_end_date'      => ['nullable', 'date'],
            'landlord_name'          => ['nullable', 'string', 'max:150'],
            'landlord_phone'         => ['nullable', 'string', 'max:30'],
            'landlord_email'         => ['nullable', 'email', 'max:150'],
            'notes'                  => ['nullable', 'string', 'max:2000'],
            'is_visible_to_student'  => ['nullable', 'boolean'],
        ]);

        $data['utilities_included']    = (bool) ($data['utilities_included'] ?? $accommodation->utilities_included);
        $data['is_visible_to_student'] = (bool) ($data['is_visible_to_student'] ?? $accommodation->is_visible_to_student);
        $accommodation->update($data);

        return back()->with('status', 'Konut kaydı güncellendi.');
    }

    public function housingDelete(Request $request, StudentAccommodation $accommodation): \Illuminate\Http\RedirectResponse
    {
        abort_if(!$this->assignedStudentIds($request)->contains($accommodation->student_id), 403);
        $accommodation->delete();
        return back()->with('status', 'Konut kaydı silindi.');
    }
}
