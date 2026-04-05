<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Models\AccountVault;
use App\Models\GuestApplication;
use App\Models\MarketingTask;
use App\Models\StudentAppointment;
use App\Models\StudentAssignment;
use App\Services\AccountVaultService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class SeniorVaultController extends Controller
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

    public function vault(Request $request)
    {
        $studentIds = $this->assignedStudentIds($request);
        $q          = trim((string) $request->query('q', ''));
        $status     = trim((string) $request->query('status', 'all'));
        $filterSid  = trim((string) $request->query('student_id', 'all'));

        $vaults = $studentIds->isEmpty()
            ? collect()
            : AccountVault::query()
                ->whereIn('student_id', $studentIds->all())
                ->when($q !== '', function ($w) use ($q) {
                    $w->where(function ($x) use ($q) {
                        $x->where('student_id', 'like', "%{$q}%")
                            ->orWhere('service_name', 'like', "%{$q}%")
                            ->orWhere('service_label', 'like', "%{$q}%")
                            ->orWhere('account_email', 'like', "%{$q}%")
                            ->orWhere('account_username', 'like', "%{$q}%");
                    });
                })
                ->when($status !== '' && $status !== 'all', fn ($w) => $w->where('status', $status))
                ->when($filterSid !== '' && $filterSid !== 'all', fn ($w) => $w->where('student_id', $filterSid))
                ->latest()
                ->paginate(20, ['id', 'student_id', 'service_name', 'service_label', 'account_url', 'account_email', 'account_username', 'application_id', 'notes', 'status', 'is_visible_to_student', 'created_by'])
                ->withQueryString();

        $guestMap = GuestApplication::query()
            ->whereIn('converted_student_id', $studentIds->all())
            ->latest('id')
            ->get(['first_name', 'last_name', 'converted_student_id'])
            ->keyBy('converted_student_id');

        $studentOptions = $studentIds->map(function ($sid) use ($guestMap) {
            $g    = $guestMap->get($sid);
            $name = $g ? trim((string) $g->first_name . ' ' . (string) $g->last_name) : '';
            return ['id' => $sid, 'label' => $name !== '' ? "{$sid} — {$name}" : $sid];
        })->values();

        return view('senior.vault', [
            'vaults'         => $vaults,
            'studentOptions' => $studentOptions,
            'filters'        => compact('q', 'status', 'filterSid'),
            'sidebarStats'   => $this->sidebarStats($request),
        ]);
    }

    public function storeVault(Request $request, AccountVaultService $service): \Illuminate\Http\RedirectResponse
    {
        $studentIds = $this->assignedStudentIds($request)->all();

        $data = $request->validate([
            'student_id'            => ['required', 'string', 'max:64', Rule::in($studentIds)],
            'service_name'          => ['required', 'string', 'max:64'],
            'service_label'         => ['required', 'string', 'max:255'],
            'account_url'           => ['nullable', 'url', 'max:500'],
            'account_email'         => ['required', 'email', 'max:255'],
            'account_username'      => ['nullable', 'string', 'max:255'],
            'account_password'      => ['required', 'string', 'min:4'],
            'application_id'        => ['nullable', 'string', 'max:64'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'is_visible_to_student' => ['nullable', 'boolean'],
        ]);

        $service->create($data, $request);

        return back()->with('status', 'Vault girdisi eklendi.');
    }

    public function destroyVault(Request $request, AccountVault $vault, AccountVaultService $service): \Illuminate\Http\RedirectResponse
    {
        $studentIds = $this->assignedStudentIds($request)->all();
        if (!in_array((string) $vault->student_id, $studentIds, true)) {
            abort(403);
        }

        $service->delete($vault, $request);

        return back()->with('status', 'Vault girdisi silindi.');
    }

    public function toggleVaultVisibility(Request $request, AccountVault $vault): \Illuminate\Http\RedirectResponse
    {
        $studentIds = $this->assignedStudentIds($request)->all();
        if (!in_array((string) $vault->student_id, $studentIds, true)) {
            abort(403);
        }

        $vault->update(['is_visible_to_student' => !(bool) $vault->is_visible_to_student]);

        $state = $vault->is_visible_to_student ? 'öğrenciye açıldı' : 'öğrenciden gizlendi';

        return back()->with('status', "Vault #{$vault->id} {$state}.");
    }
}
