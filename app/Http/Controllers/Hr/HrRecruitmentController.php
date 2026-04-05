<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\HrCandidate;
use App\Models\Hr\HrInterview;
use App\Models\Hr\HrJobPosting;
use App\Models\Hr\HrOnboardingTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HrRecruitmentController extends Controller
{
    private function companyId(): int
    {
        return (int) (auth()->user()?->company_id ?? 0);
    }

    // ── İş ilanları ──────────────────────────────────────────────────────────

    public function postings(Request $request)
    {
        $cid      = $this->companyId();
        $status   = $request->query('status', '');

        $postings = HrJobPosting::when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->withCount('candidates')
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total'  => $postings->count(),
            'active' => $postings->where('status', 'active')->count(),
            'draft'  => $postings->where('status', 'draft')->count(),
            'closed' => $postings->where('status', 'closed')->count(),
        ];

        return view('manager.hr.recruitment.postings', compact('postings', 'stats', 'status'));
    }

    public function storePosting(Request $request)
    {
        $cid  = $this->companyId();
        $data = $request->validate([
            'title'           => 'required|string|max:150',
            'department'      => 'nullable|string|max:80',
            'role_type'       => 'nullable|string|max:80',
            'employment_type' => 'required|in:full_time,part_time,internship,freelance',
            'description'     => 'nullable|string|max:5000',
            'requirements'    => 'nullable|string|max:3000',
            'is_remote'       => 'boolean',
            'location'        => 'nullable|string|max:100',
            'salary_min'      => 'nullable|numeric|min:0',
            'salary_max'      => 'nullable|numeric|min:0',
            'currency'        => 'nullable|string|size:3',
            'deadline_at'     => 'nullable|date',
            'status'          => 'required|in:draft,active,paused,closed',
        ]);

        $data['company_id']  = $cid ?: null;
        $data['created_by']  = auth()->id();
        $data['is_remote']   = $request->boolean('is_remote');
        if ($data['status'] === 'active') {
            $data['published_at'] = now();
        }

        HrJobPosting::create($data);

        return redirect('/manager/hr/recruitment')->with('status', 'İlan oluşturuldu.');
    }

    public function updatePosting(Request $request, HrJobPosting $posting)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $posting->company_id !== $cid, 403);

        $data = $request->validate([
            'title'           => 'required|string|max:150',
            'department'      => 'nullable|string|max:80',
            'employment_type' => 'required|in:full_time,part_time,internship,freelance',
            'description'     => 'nullable|string|max:5000',
            'requirements'    => 'nullable|string|max:3000',
            'is_remote'       => 'boolean',
            'location'        => 'nullable|string|max:100',
            'salary_min'      => 'nullable|numeric|min:0',
            'salary_max'      => 'nullable|numeric|min:0',
            'status'          => 'required|in:draft,active,paused,closed',
            'deadline_at'     => 'nullable|date',
        ]);

        $data['is_remote'] = $request->boolean('is_remote');
        if ($data['status'] === 'active' && !$posting->published_at) {
            $data['published_at'] = now();
        }

        $posting->update($data);

        return back()->with('status', 'İlan güncellendi.');
    }

    // ── Adaylar ──────────────────────────────────────────────────────────────

    public function candidates(Request $request)
    {
        $cid       = $this->companyId();
        $status    = $request->query('status', '');
        $postingId = $request->query('posting_id', '');

        $candidates = HrCandidate::with(['posting:id,title', 'assignedTo:id,name'])
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->when($postingId !== '', fn($q) => $q->where('job_posting_id', $postingId))
            ->orderByDesc('created_at')
            ->get();

        $postings = HrJobPosting::when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->whereIn('status', ['active', 'paused'])
            ->orderBy('title')
            ->get(['id', 'title']);

        $pipeline = collect(array_keys(HrCandidate::$statusLabels))->mapWithKeys(fn($s) => [
            $s => $candidates->where('status', $s)->count(),
        ]);

        $team = User::whereIn('role', ['manager', 'operations_admin', 'operations_staff'])
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('manager.hr.recruitment.candidates', compact(
            'candidates', 'postings', 'pipeline', 'team', 'status', 'postingId'
        ));
    }

    public function storeCandidate(Request $request)
    {
        $cid  = $this->companyId();
        $data = $request->validate([
            'job_posting_id' => 'nullable|exists:hr_job_postings,id',
            'first_name'     => 'required|string|max:80',
            'last_name'      => 'required|string|max:80',
            'email'          => 'nullable|email|max:200',
            'phone'          => 'nullable|string|max:30',
            'source'         => 'required|in:linkedin,referral,website,agency,direct',
            'notes'          => 'nullable|string|max:1000',
            'linkedin_url'   => 'nullable|url|max:400',
            'portfolio_url'  => 'nullable|url|max:400',
            'assigned_to'    => 'nullable|exists:users,id',
        ]);

        // CV yükle
        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store('hr_cvs', 'local');
        }

        $data['company_id'] = $cid ?: null;
        $data['status']     = 'applied';

        HrCandidate::create($data);

        return back()->with('status', 'Aday eklendi.');
    }

    public function candidateDetail(Request $request, HrCandidate $candidate)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $candidate->company_id !== $cid, 403);

        $candidate->load(['posting:id,title', 'interviews.interviewer:id,name', 'assignedTo:id,name']);

        $team = User::whereIn('role', ['manager', 'operations_admin', 'operations_staff', 'senior'])
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('manager.hr.recruitment.candidate-detail', compact('candidate', 'team'));
    }

    public function updateCandidateStatus(Request $request, HrCandidate $candidate)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $candidate->company_id !== $cid, 403);

        $data = $request->validate([
            'status'           => 'required|in:applied,screening,interview,offer,hired,rejected',
            'rejection_reason' => 'nullable|string|max:300',
            'rating'           => 'nullable|integer|min:1|max:5',
            'notes'            => 'nullable|string|max:1000',
            'assigned_to'      => 'nullable|exists:users,id',
        ]);

        $candidate->update($data);

        // İşe alındıysa onboarding görevleri oluştur
        if ($data['status'] === 'hired' && $candidate->email) {
            $this->createOnboardingTasks($candidate, $cid);
        }

        return back()->with('status', 'Aday durumu güncellendi.');
    }

    private function createOnboardingTasks(HrCandidate $candidate, int $cid): void
    {
        // Sistemde bu e-posta ile kullanıcı varsa onboarding görevleri oluştur
        $user = User::where('email', $candidate->email)->first();
        if (!$user) {
            return;
        }

        // Zaten görev varsa oluşturma
        if (HrOnboardingTask::where('user_id', $user->id)->exists()) {
            return;
        }

        foreach (HrOnboardingTask::$defaultTasks as $week => $tasks) {
            foreach ($tasks as $i => $title) {
                HrOnboardingTask::create([
                    'company_id' => $cid ?: null,
                    'user_id'    => $user->id,
                    'week'       => $week,
                    'title'      => $title,
                    'sort_order' => $i,
                ]);
            }
        }
    }

    // ── Mülakatlar ────────────────────────────────────────────────────────────

    public function storeInterview(Request $request, HrCandidate $candidate)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $candidate->company_id !== $cid, 403);

        $data = $request->validate([
            'interviewer_user_id' => 'required|exists:users,id',
            'scheduled_at'        => 'required|date',
            'duration_minutes'    => 'required|integer|min:15|max:240',
            'type'                => 'required|in:phone,video,onsite,technical',
        ]);

        $data['candidate_id'] = $candidate->id;
        $data['status']       = 'scheduled';

        HrInterview::create($data);

        // Adayı interview aşamasına taşı
        if ($candidate->status === 'applied' || $candidate->status === 'screening') {
            $candidate->update(['status' => 'interview']);
        }

        return back()->with('status', 'Mülakat planlandı.');
    }

    public function updateInterview(Request $request, HrInterview $interview)
    {
        $data = $request->validate([
            'status'         => 'required|in:scheduled,completed,cancelled,no_show',
            'score'          => 'nullable|integer|min:1|max:10',
            'feedback'       => 'nullable|string|max:2000',
            'recommendation' => 'nullable|in:hire,reject,maybe',
        ]);

        $interview->update($data);

        return back()->with('status', 'Mülakat güncellendi.');
    }

    // ── Onboarding ────────────────────────────────────────────────────────────

    public function onboarding(Request $request)
    {
        $cid = $this->companyId();

        // Son 90 günde işe alınan çalışanlar
        $employees = User::whereIn('role', array_merge(
            ['manager', 'senior', 'system_admin', 'system_staff', 'operations_admin', 'operations_staff',
             'finance_admin', 'finance_staff', 'marketing_admin', 'marketing_staff', 'sales_admin', 'sales_staff']
        ))
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->where('created_at', '>=', now()->subDays(90))
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'role', 'created_at']);

        $onboardingData = $employees->mapWithKeys(function ($emp) {
            $tasks = HrOnboardingTask::where('user_id', $emp->id)
                ->orderBy('week')
                ->orderBy('sort_order')
                ->get();
            $total    = $tasks->count();
            $done     = $tasks->where('is_done', true)->count();
            $progress = $total > 0 ? round($done / $total * 100) : 0;
            return [$emp->id => compact('tasks', 'total', 'done', 'progress')];
        });

        return view('manager.hr.recruitment.onboarding', compact('employees', 'onboardingData'));
    }

    public function toggleOnboardingTask(Request $request, HrOnboardingTask $task)
    {
        $task->update([
            'is_done'      => !$task->is_done,
            'completed_by' => $task->is_done ? null : auth()->id(),
            'completed_at' => $task->is_done ? null : now(),
        ]);

        return response()->json(['is_done' => $task->is_done]);
    }

    public function initOnboarding(Request $request, User $user)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $user->company_id !== $cid, 403);

        if (HrOnboardingTask::where('user_id', $user->id)->exists()) {
            return back()->with('status', 'Onboarding görevleri zaten oluşturulmuş.');
        }

        foreach (HrOnboardingTask::$defaultTasks as $week => $tasks) {
            foreach ($tasks as $i => $title) {
                HrOnboardingTask::create([
                    'company_id' => $cid ?: null,
                    'user_id'    => $user->id,
                    'week'       => $week,
                    'title'      => $title,
                    'sort_order' => $i,
                ]);
            }
        }

        return back()->with('status', 'Onboarding görevleri oluşturuldu.');
    }
}
