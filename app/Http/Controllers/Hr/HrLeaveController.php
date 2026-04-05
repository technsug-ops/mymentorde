<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\HrLeaveAttachment;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrPersonProfile;
use App\Services\NotificationService;
use App\Support\FileUploadRules;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HrLeaveController extends Controller
{
    private const ALL_EMPLOYEE_ROLES = [
        'manager', 'senior',
        'system_admin', 'system_staff',
        'operations_admin', 'operations_staff',
        'finance_admin', 'finance_staff',
        'marketing_admin', 'marketing_staff',
        'sales_admin', 'sales_staff',
    ];

    private const MANAGER_ROLES = ['manager', 'system_admin', 'operations_admin'];

    private function companyId(): int
    {
        return (int) (auth()->user()?->company_id ?? 0);
    }

    private function calcDays(string $start, string $end): int
    {
        return max(1, Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1);
    }

    // ── Manager Views ─────────────────────────────────────────────────────────

    public function managerIndex(Request $request)
    {
        $cid          = $this->companyId();
        $statusFilter = $request->query('status', 'pending');
        $typeFilter   = $request->query('type', '');
        $userFilter   = $request->query('user_id', '');

        $employeeIds = User::whereIn('role', self::ALL_EMPLOYEE_ROLES)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->pluck('id')
            ->all();

        $pendingLeaves = HrLeaveRequest::whereIn('user_id', $employeeIds)
            ->with(['user:id,name,email,role', 'attachments'])
            ->where('status', 'pending')
            ->when($typeFilter !== '', fn($q) => $q->where('leave_type', $typeFilter))
            ->when($userFilter !== '', fn($q) => $q->where('user_id', $userFilter))
            ->orderBy('start_date')
            ->get();

        // Upcoming confirmed leaves (approved, start_date within next 90 days)
        $upcomingLeaves = HrLeaveRequest::whereIn('user_id', $employeeIds)
            ->with(['user:id,name,role', 'deputy:id,name'])
            ->where('status', 'approved')
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->get();

        $employees = User::whereIn('role', self::ALL_EMPLOYEE_ROLES)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->orderBy('name')
            ->get(['id', 'name']);

        $pendingCount = $pendingLeaves->count();

        return view('manager.hr.leaves.index', compact(
            'pendingLeaves', 'upcomingLeaves', 'employees', 'pendingCount',
            'statusFilter', 'typeFilter', 'userFilter'
        ));
    }

    public function managerOwnStore(Request $request)
    {
        $user = auth()->user();
        $cid  = $this->companyId();
        $data = $request->validate([
            'leave_type'     => 'required|in:annual,sick,personal,maternity,unpaid',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'reason'         => 'nullable|string|max:500',
            'deputy_user_id' => 'nullable|exists:users,id',
        ]);

        HrLeaveRequest::create([
            'user_id'        => $user->id,
            'company_id'     => $cid ?: null,
            'leave_type'     => $data['leave_type'],
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'],
            'days_count'     => $this->calcDays($data['start_date'], $data['end_date']),
            'status'         => 'approved',
            'reason'         => $data['reason'] ?? null,
            'deputy_user_id' => $data['deputy_user_id'] ?? null,
            'approved_by'    => $user->id,
            'approved_at'    => now(),
        ]);

        return back()->with('status', 'Yokluğunuz takvime eklendi.');
    }

    public function store(Request $request)
    {
        $cid  = $this->companyId();
        $data = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'leave_type' => 'required|in:annual,sick,personal,maternity,unpaid',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string|max:500',
            'status'     => 'required|in:pending,approved',
        ]);

        $data['days_count']  = $this->calcDays($data['start_date'], $data['end_date']);
        $data['company_id']  = $cid ?: null;
        if ($data['status'] === 'approved') {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        HrLeaveRequest::create($data);

        return back()->with('status', 'İzin kaydı oluşturuldu.');
    }

    public function approve(HrLeaveRequest $leave)
    {
        abort_unless($leave->status === 'pending', 422);
        $leave->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        \Illuminate\Support\Facades\Cache::forget('mgr_pending_leaves_' . ((int) ($leave->company_id ?? 0)));

        // Çalışana bildirim gönder
        try {
            $employee = $leave->user;
            if ($employee) {
                $typeLabels = HrLeaveRequest::$typeLabels ?? [];
                app(NotificationService::class)->send([
                    'channel'        => 'in_app',
                    'category'       => 'hr_leave_approved',
                    'user_id'        => $employee->id,
                    'company_id'     => $leave->company_id,
                    'recipient_name' => $employee->name,
                    'body'           => config('notification_templates.hr_leave_approved.body_tr'),
                    'variables'      => [
                        'employee_name' => $employee->name,
                        'start_date'    => $leave->start_date->format('d.m.Y'),
                        'end_date'      => $leave->end_date->format('d.m.Y'),
                        'days_count'    => $leave->days_count,
                        'leave_type'    => $typeLabels[$leave->leave_type] ?? $leave->leave_type,
                    ],
                    'source_type'    => 'hr_leave',
                    'source_id'      => (string) $leave->id,
                    'triggered_by'   => auth()->user()?->email,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('HR leave approve notification failed: ' . $e->getMessage());
        }

        return back()->with('status', 'İzin onaylandı.');
    }

    public function reject(Request $request, HrLeaveRequest $leave)
    {
        abort_unless($leave->status === 'pending', 422);
        $data = $request->validate(['rejection_note' => 'nullable|string|max:500']);
        $leave->update(['status' => 'rejected', 'rejection_note' => $data['rejection_note'] ?? null]);
        \Illuminate\Support\Facades\Cache::forget('mgr_pending_leaves_' . ((int) ($leave->company_id ?? 0)));

        // Çalışana bildirim gönder
        try {
            $employee = $leave->user;
            if ($employee) {
                $note = $data['rejection_note'] ? ' Sebep: ' . $data['rejection_note'] : '';
                app(NotificationService::class)->send([
                    'channel'        => 'in_app',
                    'category'       => 'hr_leave_rejected',
                    'user_id'        => $employee->id,
                    'company_id'     => $leave->company_id,
                    'recipient_name' => $employee->name,
                    'body'           => config('notification_templates.hr_leave_rejected.body_tr'),
                    'variables'      => [
                        'employee_name'  => $employee->name,
                        'start_date'     => $leave->start_date->format('d.m.Y'),
                        'end_date'       => $leave->end_date->format('d.m.Y'),
                        'rejection_note' => $note,
                    ],
                    'source_type'    => 'hr_leave',
                    'source_id'      => (string) $leave->id,
                    'triggered_by'   => auth()->user()?->email,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('HR leave reject notification failed: ' . $e->getMessage());
        }

        return back()->with('status', 'İzin reddedildi.');
    }

    // ── Self-servis ───────────────────────────────────────────────────────────

    public function myLeaves(Request $request)
    {
        $user    = auth()->user();
        $year    = (int) $request->query('year', now()->year);
        $profile = HrPersonProfile::firstOrNew(['user_id' => $user->id]);
        $quota   = $profile->annual_leave_quota ?? 14;
        $used    = $profile->exists ? $profile->usedLeaveDays($year) : 0;

        $leaves = HrLeaveRequest::where('user_id', $user->id)
            ->orderByDesc('start_date')
            ->get();

        return view('hr.my.leaves', compact('user', 'leaves', 'profile', 'quota', 'used', 'year'));
    }

    public function myStore(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'leave_type'         => 'required|in:annual,sick,personal,maternity,unpaid',
            'start_date'         => 'required|date|after_or_equal:today',
            'end_date'           => 'required|date|after_or_equal:start_date',
            'reason'             => 'nullable|string|max:500',
            'attachments'        => 'nullable|array|max:5',
            'attachments.*'      => FileUploadRules::attachment(),
            'attachment_links'   => 'nullable|array|max:5',
            'attachment_links.*' => 'nullable|url|max:1000',
        ]);

        $leave = HrLeaveRequest::create([
            'user_id'    => $user->id,
            'company_id' => $user->company_id,
            'leave_type' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'days_count' => $this->calcDays($data['start_date'], $data['end_date']),
            'status'     => 'pending',
            'reason'     => $data['reason'] ?? null,
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('hr_leave_attachments/' . $leave->id, 'local');
            HrLeaveAttachment::create([
                'leave_request_id' => $leave->id,
                'type'             => 'file',
                'original_name'    => $file->getClientOriginalName(),
                'path'             => $path,
                'uploaded_by'      => $user->id,
            ]);
        }

        foreach (array_filter($request->input('attachment_links', [])) as $link) {
            HrLeaveAttachment::create([
                'leave_request_id' => $leave->id,
                'type'             => 'link',
                'url'              => $link,
                'uploaded_by'      => $user->id,
            ]);
        }

        \Illuminate\Support\Facades\Cache::forget('mgr_pending_leaves_' . ((int) ($user->company_id ?? 0)));

        // Manager'a yeni izin talebi bildirimi
        try {
            $managers = User::whereIn('role', self::MANAGER_ROLES)
                ->where('company_id', $user->company_id)
                ->get(['id', 'name']);
            $typeLabels = HrLeaveRequest::$typeLabels ?? [];
            foreach ($managers as $mgr) {
                app(NotificationService::class)->send([
                    'channel'    => 'in_app',
                    'category'   => 'hr_leave_new_request',
                    'user_id'    => $mgr->id,
                    'company_id' => $user->company_id,
                    'variables'  => [
                        'employee_name' => $user->name,
                        'start_date'    => $data['start_date'],
                        'end_date'      => $data['end_date'],
                        'days_count'    => $this->calcDays($data['start_date'], $data['end_date']),
                        'leave_type'    => $typeLabels[$data['leave_type']] ?? $data['leave_type'],
                    ],
                    'source_type' => 'hr_leave',
                    'source_id'   => (string) $leave->id,
                    'triggered_by'=> $user->email,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('HR leave new request notification failed: ' . $e->getMessage());
        }

        return back()->with('status', 'İzin talebiniz oluşturuldu. Onay bekleniyor.');
    }

    public function downloadAttachment(HrLeaveAttachment $attachment)
    {
        $user  = auth()->user();
        $leave = $attachment->leaveRequest;
        $isSelf    = $leave->user_id === $user->id;
        $isManager = in_array($user->role, ['manager', 'system_admin', 'operations_admin', 'operations_staff'], true);
        abort_unless($isSelf || $isManager, 403);
        abort_unless($attachment->type === 'file' && $attachment->path, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function myCancel(HrLeaveRequest $leave)
    {
        abort_unless($leave->user_id === auth()->id() && $leave->status === 'pending', 403);
        $leave->update(['status' => 'cancelled']);
        return back()->with('status', 'İzin talebiniz iptal edildi.');
    }
}
