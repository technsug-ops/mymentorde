<?php

namespace App\Http\Controllers;

use App\Http\Controllers\TaskBoard\Concerns\TaskBoardTrait;
use App\Models\GuestTicket;
use App\Models\MarketingTask;
use App\Models\StudentAssignment;
use App\Models\TaskActivityLog;
use App\Models\TaskChecklist;
use App\Models\TaskTemplateItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskBoardController extends Controller
{
    use TaskBoardTrait;

    public function index(Request $request, ?string $department = null)
    {
        $user   = $request->user();
        $role   = (string) ($user->role ?? '');
        $userId = (int) $user->id;

        $isGlobalViewer       = $this->isGlobalViewer($role);
        $isDeptAdmin          = $this->isDeptAdmin($role);
        $roleScopedDepartment = $this->resolveScopedDepartmentForRole($role);

        $routeDepartment = trim((string) $department);
        if ($routeDepartment !== '' && ! array_key_exists($routeDepartment, $this->departmentOptions())) {
            abort(404);
        }
        if ($roleScopedDepartment !== null && $routeDepartment !== '' && $routeDepartment !== $roleScopedDepartment) {
            abort(403, 'Bu departman kuyruğunu görüntüleme yetkiniz yok.');
        }

        $filters = [
            'status'       => trim((string) $request->query('status', '')),
            'priority'     => trim((string) $request->query('priority', '')),
            'assignee'     => (int) $request->query('assignee', 0),
            'source_type'  => trim((string) $request->query('source_type', '')),
            'department'   => $routeDepartment !== '' ? $routeDepartment : trim((string) $request->query('department', '')),
            'sla'          => trim((string) $request->query('sla', '')),
            'dependency'   => trim((string) $request->query('dependency', '')),
            'recurring'    => trim((string) $request->query('recurring', '')),
            'process_type' => trim((string) $request->query('process_type', '')),
            'due_from'     => trim((string) $request->query('due_from', '')),
            'due_to'       => trim((string) $request->query('due_to', '')),
        ];
        if ($roleScopedDepartment !== null) {
            $filters['department'] = $roleScopedDepartment;
        }

        $seniorStudentSources = [];
        if ($role === User::ROLE_SENIOR || $role === User::ROLE_MENTOR) {
            $seniorEmail = strtolower(trim((string) ($user->email ?? '')));
            if ($seniorEmail !== '') {
                $seniorStudentSources = StudentAssignment::query()
                    ->where('senior_email', $seniorEmail)
                    ->pluck('student_id')
                    ->map(fn ($sid) => (string) $sid)
                    ->toArray();
            }
        }

        $ticketSourceTypes = ['guest_ticket_opened', 'guest_ticket_reply', 'guest_ticket_replied'];

        $rows = MarketingTask::query()
            ->with(['assignedUser:id,name,email,role', 'createdByUser:id,name,email,role', 'dependsOn:id,title,status', 'checklists', 'watchers'])
            ->whereNotIn('source_type', $ticketSourceTypes)
            ->when(! $isGlobalViewer, function ($q) use ($isDeptAdmin, $roleScopedDepartment, $userId, $seniorStudentSources): void {
                if ($isDeptAdmin) {
                    if ($roleScopedDepartment !== null) {
                        $q->where('department', $roleScopedDepartment);
                    }
                } else {
                    $q->where(function ($sub) use ($userId, $seniorStudentSources): void {
                        $sub->where('assigned_user_id', $userId)
                            ->orWhere('created_by_user_id', $userId);
                        if (! empty($seniorStudentSources)) {
                            $sub->orWhereIn('source_id', $seniorStudentSources);
                        }
                    });
                }
            })
            ->when($filters['status'] !== '', function ($q) use ($filters) {
                if ($filters['status'] === 'hold_block') {
                    $q->whereIn('status', ['on_hold', 'blocked']);
                } else {
                    $q->where('status', $filters['status']);
                }
            })
            ->when($filters['priority'] !== '', fn ($q) => $q->where('priority', $filters['priority']))
            ->when($filters['assignee'] > 0, fn ($q) => $q->where('assigned_user_id', $filters['assignee']))
            ->when($filters['source_type'] !== '', fn ($q) => $q->where('source_type', $filters['source_type']))
            ->when($filters['department'] !== '', fn ($q) => $q->where('department', $filters['department']))
            ->when($filters['sla'] === 'warn', fn ($q) => $q->whereDate('due_date', now()->toDateString())->whereNotIn('status', ['done', 'cancelled']))
            ->when($filters['sla'] === 'overdue', fn ($q) => $q->whereDate('due_date', '<', now()->toDateString())->whereNotIn('status', ['done', 'cancelled']))
            ->when($filters['dependency'] === 'yes', fn ($q) => $q->whereNotNull('depends_on_task_id'))
            ->when($filters['dependency'] === 'no', fn ($q) => $q->whereNull('depends_on_task_id'))
            ->when($filters['recurring'] === 'yes', fn ($q) => $q->where('is_recurring', true))
            ->when($filters['recurring'] === 'no', fn ($q) => $q->where('is_recurring', false))
            ->when($filters['process_type'] !== '', fn ($q) => $q->where('process_type', $filters['process_type']))
            ->when($filters['due_from'] !== '', fn ($q) => $q->whereDate('due_date', '>=', $filters['due_from']))
            ->when($filters['due_to'] !== '', fn ($q) => $q->whereDate('due_date', '<=', $filters['due_to']))
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END ASC")
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $statsBase = MarketingTask::query()->whereNotIn('source_type', $ticketSourceTypes);
        if (! $isGlobalViewer) {
            if ($isDeptAdmin && $roleScopedDepartment !== null) {
                $statsBase->where('department', $roleScopedDepartment);
            } elseif (! $isDeptAdmin) {
                $statsBase->where(function ($q) use ($userId, $seniorStudentSources): void {
                    $q->where('assigned_user_id', $userId)->orWhere('created_by_user_id', $userId);
                    if (! empty($seniorStudentSources)) {
                        $q->orWhereIn('source_id', $seniorStudentSources);
                    }
                });
            }
        }
        if ($filters['department'] !== '') {
            $statsBase->where('department', $filters['department']);
        }

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $tickets = GuestTicket::query()
            ->with(['guestApplication:id,first_name,last_name,email', 'assignedUser:id,name'])
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->when($roleScopedDepartment !== null, fn ($q) => $q->where('department', $roleScopedDepartment))
            ->whereNotIn('status', ['closed'])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 WHEN status = 'in_progress' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $recentTasks = MarketingTask::query()
            ->whereNotIn('source_type', $ticketSourceTypes)
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'title', 'status']);

        return view('tasks.index', [
            'pageTitle'            => 'Task Board',
            'rows'                 => $rows,
            'tickets'              => $tickets,
            'filters'              => $filters,
            'statusOptions'        => $this->statusOptions(),
            'priorityOptions'      => $this->priorityOptions(),
            'departmentOptions'    => $this->departmentOptions(),
            'processTypeOptions'   => $this->processTypeOptions(),
            'sourceOptions'        => $this->sourceOptions(),
            'assignees'            => $this->assignees($role, $userId),
            'isGlobalViewer'       => $isGlobalViewer,
            'isDeptAdmin'          => $isDeptAdmin,
            'roleScopedDepartment' => $roleScopedDepartment,
            'routeDepartment'      => $routeDepartment,
            'recentTasks'          => $recentTasks,
            'stats' => [
                'total'       => (int) (clone $statsBase)->count(),
                'todo'        => (int) (clone $statsBase)->where('status', 'todo')->count(),
                'in_progress' => (int) (clone $statsBase)->where('status', 'in_progress')->count(),
                'in_review'   => (int) (clone $statsBase)->where('status', 'in_review')->count(),
                'on_hold'     => (int) (clone $statsBase)->where('status', 'on_hold')->count(),
                'blocked'     => (int) (clone $statsBase)->where('status', 'blocked')->count(),
                'done'        => (int) (clone $statsBase)->where('status', 'done')->count(),
                'cancelled'   => (int) (clone $statsBase)->where('status', 'cancelled')->count(),
                'overdue'     => (int) (clone $statsBase)
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user   = $request->user();
        $role   = (string) optional($user)->role;
        $userId = (int) optional($user)->id;
        $roleScopedDepartment = $this->resolveScopedDepartmentForRole($role);
        $isDeptAdmin = $this->isDeptAdmin($role);

        $data = $request->validate([
            'title'                    => ['required', 'string', 'max:190'],
            'description'              => ['nullable', 'string', 'max:2000'],
            'status'                   => ['required', 'string', 'in:'.implode(',', array_keys($this->statusOptions()))],
            'priority'                 => ['required', 'string', 'in:'.implode(',', array_keys($this->priorityOptions()))],
            'department'               => ['required', 'string', 'in:'.implode(',', array_keys($this->departmentOptions()))],
            'due_date'                 => ['nullable', 'date'],
            'assigned_user_id'         => ['nullable', 'integer'],
            'depends_on_task_id'       => ['nullable', 'integer'],
            'is_recurring'             => ['nullable', 'boolean'],
            'recurrence_pattern'       => ['nullable', 'string', 'in:daily,weekly,monthly'],
            'recurrence_interval_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'escalate_after_hours'     => ['nullable', 'integer', 'min:1', 'max:720'],
            'process_type'             => ['nullable', 'string', 'in:'.implode(',', array_keys($this->processTypeOptions()))],
            'workflow_stage'           => ['nullable', 'string', 'max:64'],
            'template_id'              => ['nullable', 'integer', 'exists:task_templates,id'],
            'actual_hours'             => ['nullable', 'numeric', 'min:0', 'max:999'],
        ]);

        $priority     = (string) $data['priority'];
        $isRecurring  = (bool) ($data['is_recurring'] ?? false);
        $intervalDays = (int) ($data['recurrence_interval_days'] ?? 0);
        $nextRunAt    = $isRecurring ? now()->addDays(max(1, $intervalDays > 0 ? $intervalDays : 7)) : null;

        $assignedUserId = ((int) ($data['assigned_user_id'] ?? 0)) ?: null;
        if (! $isDeptAdmin && ! $this->isGlobalViewer($role)) {
            $assignedUserId = $userId ?: null;
        }

        $dependsOnTaskId = ((int) ($data['depends_on_task_id'] ?? 0)) ?: null;
        $status = (string) $data['status'];
        if ($dependsOnTaskId) {
            $status = 'blocked';
        }

        $task = MarketingTask::query()->create([
            'title'                    => trim((string) $data['title']),
            'description'              => trim((string) ($data['description'] ?? '')),
            'status'                   => $status,
            'priority'                 => $priority,
            'department'               => $roleScopedDepartment ?? (string) $data['department'],
            'due_date'                 => $data['due_date'] ?? null,
            'assigned_user_id'         => $assignedUserId,
            'created_by_user_id'       => $userId ?: null,
            'completed_at'             => $status === 'done' ? now() : null,
            'depends_on_task_id'       => $dependsOnTaskId,
            'is_recurring'             => $isRecurring,
            'recurrence_pattern'       => $isRecurring ? (string) ($data['recurrence_pattern'] ?? 'weekly') : null,
            'recurrence_interval_days' => $isRecurring ? max(1, $intervalDays > 0 ? $intervalDays : 7) : null,
            'next_run_at'              => $nextRunAt,
            'escalate_after_hours'     => (int) ($data['escalate_after_hours'] ?? MarketingTask::defaultSlaHours($priority)),
            'process_type'             => ($data['process_type'] ?? null) ?: null,
            'workflow_stage'           => ($data['workflow_stage'] ?? null) ?: null,
            'template_id'              => ((int) ($data['template_id'] ?? 0)) ?: null,
            'actual_hours'             => isset($data['actual_hours']) ? (float) $data['actual_hours'] : null,
        ]);

        TaskActivityLog::record((int) $task->id, $userId, 'created', null, (string) $task->id);

        if ($dependsOnTaskId) {
            TaskActivityLog::record((int) $task->id, $userId, 'dependency_set', null, 'depends_on:' . $dependsOnTaskId);
        }

        if ($task->template_id) {
            $items = TaskTemplateItem::query()
                ->where('template_id', $task->template_id)
                ->orderBy('sort_order')
                ->get();

            if ($items->isNotEmpty()) {
                foreach ($items as $item) {
                    TaskChecklist::create([
                        'task_id'    => $task->id,
                        'title'      => $item->label,
                        'is_done'    => false,
                        'sort_order' => $item->sort_order,
                    ]);
                }
                $task->update([
                    'checklist_total' => $items->count(),
                    'checklist_done'  => 0,
                ]);
                TaskActivityLog::record((int) $task->id, $userId, 'template_applied', null, (string) $task->template_id);
            }
        }

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'Task eklendi.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user   = $request->user();
        $role   = (string) optional($user)->role;
        $userId = (int) optional($user)->id;
        $isDeptAdmin = $this->isDeptAdmin($role);
        $roleScopedDepartment = $this->resolveScopedDepartmentForRole($role);

        $row = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $row)) {
            abort(403, 'Bu task kaydini guncelleyemezsiniz.');
        }

        $data = $request->validate([
            'title'                    => ['required', 'string', 'max:190'],
            'description'              => ['nullable', 'string', 'max:2000'],
            'status'                   => ['required', 'string', 'in:'.implode(',', array_keys($this->statusOptions()))],
            'priority'                 => ['required', 'string', 'in:'.implode(',', array_keys($this->priorityOptions()))],
            'department'               => ['required', 'string', 'in:'.implode(',', array_keys($this->departmentOptions()))],
            'due_date'                 => ['nullable', 'date'],
            'assigned_user_id'         => ['nullable', 'integer'],
            'hold_reason'              => ['nullable', 'string', 'max:255'],
            'is_recurring'             => ['nullable', 'boolean'],
            'recurrence_pattern'       => ['nullable', 'string', 'in:daily,weekly,monthly'],
            'recurrence_interval_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'escalate_after_hours'     => ['nullable', 'integer', 'min:1', 'max:720'],
            'actual_hours'             => ['nullable', 'numeric', 'min:0', 'max:999'],
        ]);

        $oldStatus   = (string) $row->status;
        $oldPriority = (string) $row->priority;
        $newStatus   = (string) $data['status'];
        $newPriority = (string) $data['priority'];

        if ($oldStatus !== $newStatus && ! $row->canTransitionTo($newStatus)) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => "{$oldStatus} → {$newStatus} geçişi geçerli değil."]);
        }

        $isRecurring  = (bool) ($data['is_recurring'] ?? false);
        $intervalDays = (int) ($data['recurrence_interval_days'] ?? 0);
        $nextRunAt = $row->next_run_at;
        if ($isRecurring && ! $nextRunAt) {
            $nextRunAt = now()->addDays(max(1, $intervalDays > 0 ? $intervalDays : 7));
        }
        if (! $isRecurring) {
            $nextRunAt = null;
        }

        $assignedUserId = $row->assigned_user_id;
        if ($isDeptAdmin || $this->isGlobalViewer($role)) {
            $assignedUserId = ((int) ($data['assigned_user_id'] ?? 0)) ?: null;
        }

        $statusPayload = $this->buildStatusPayload($row, $newStatus, $userId, $data);
        $row->update(array_merge([
            'title'                    => trim((string) $data['title']),
            'description'              => trim((string) ($data['description'] ?? '')),
            'status'                   => $newStatus,
            'priority'                 => $newPriority,
            'department'               => $roleScopedDepartment ?? (string) $data['department'],
            'due_date'                 => $data['due_date'] ?? null,
            'assigned_user_id'         => $assignedUserId,
            'is_recurring'             => $isRecurring,
            'recurrence_pattern'       => $isRecurring ? (string) ($data['recurrence_pattern'] ?? 'weekly') : null,
            'recurrence_interval_days' => $isRecurring ? max(1, $intervalDays > 0 ? $intervalDays : 7) : null,
            'next_run_at'              => $nextRunAt,
            'escalate_after_hours'     => (int) ($data['escalate_after_hours'] ?? MarketingTask::defaultSlaHours($newPriority)),
            'actual_hours'             => isset($data['actual_hours']) ? (float) $data['actual_hours'] : $row->actual_hours,
        ], $statusPayload));

        if ($oldStatus !== $newStatus) {
            TaskActivityLog::record($id, $userId, 'status_changed', $oldStatus, $newStatus);
            if ($newStatus === 'done') {
                $this->resolveDependents($id, $userId);
            }
        }
        if ($oldPriority !== $newPriority) {
            TaskActivityLog::record($id, $userId, 'priority_changed', $oldPriority, $newPriority);
        }

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'Task guncellendi.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $user   = $request->user();
        $role   = (string) optional($user)->role;
        $userId = (int) optional($user)->id;
        $isDeptAdmin = $this->isDeptAdmin($role);
        $roleScopedDepartment = $this->resolveScopedDepartmentForRole($role);

        $data = $request->validate([
            'task_ids'         => ['required', 'array', 'min:1'],
            'task_ids.*'       => ['integer'],
            'status'           => ['nullable', 'string', 'in:'.implode(',', array_keys($this->statusOptions()))],
            'priority'         => ['nullable', 'string', 'in:'.implode(',', array_keys($this->priorityOptions()))],
            'department'       => ['nullable', 'string', 'in:'.implode(',', array_keys($this->departmentOptions()))],
            'assigned_user_id' => ['nullable', 'integer'],
        ]);

        $ids = collect((array) $data['task_ids'])
            ->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->unique()->values();

        if ($ids->isEmpty()) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['bulk' => 'Toplu guncelleme icin task secin.']);
        }

        $rows = MarketingTask::query()->whereIn('id', $ids->all())->get();
        $updated = 0;
        foreach ($rows as $row) {
            if (! $this->canManage($request, $row)) {
                continue;
            }
            $payload   = [];
            $newStatus = (string) ($data['status'] ?? '');
            if ($newStatus !== '') {
                $payload['status']       = $newStatus;
                $payload['completed_at'] = $newStatus === 'done' ? now() : null;
            }
            if ((string) ($data['priority'] ?? '') !== '') {
                $payload['priority'] = (string) $data['priority'];
            }
            if (($isDeptAdmin || $this->isGlobalViewer($role)) && (string) ($data['department'] ?? '') !== '') {
                $payload['department'] = $roleScopedDepartment ?? (string) $data['department'];
            }
            if (($isDeptAdmin || $this->isGlobalViewer($role)) && array_key_exists('assigned_user_id', $data)) {
                $payload['assigned_user_id'] = ((int) ($data['assigned_user_id'] ?? 0)) ?: null;
            }

            if (! empty($payload)) {
                $row->update($payload);
                if ($newStatus !== '' && $newStatus === 'done') {
                    $this->resolveDependents((int) $row->id, $userId);
                }
                TaskActivityLog::record((int) $row->id, $userId, 'bulk_updated', null, null, $payload);
                $updated++;
            }
        }

        return redirect($request->headers->get('referer', '/tasks'))
            ->with('status', "{$updated} task toplu guncellendi.");
    }

    public function markDone(Request $request, int $id): RedirectResponse
    {
        $user   = $request->user();
        $role   = (string) optional($user)->role;
        $userId = (int) optional($user)->id;
        $row = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $row)) {
            abort(403, 'Bu task kaydini guncelleyemezsiniz.');
        }
        if (! $row->canTransitionTo('done')) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => 'Bu durumdan done\'a geçiş yapılamaz.']);
        }
        if ((string) $row->status === 'in_review'
            && (int) $row->assigned_user_id === $userId
            && ! $this->isGlobalViewer($role)
        ) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => 'Kendi görevinizi onaylayamazsınız. Yönetici onayı gerekli.']);
        }
        $old = (string) $row->status;
        $row->update(['status' => 'done', 'completed_at' => now(), 'hold_reason' => null]);
        TaskActivityLog::record($id, $userId, 'status_changed', $old, 'done');
        $this->resolveDependents($id, $userId);

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'Task tamamlandi.');
    }

    public function reopen(Request $request, int $id): RedirectResponse
    {
        $userId = (int) optional($request->user())->id;
        $row = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $row)) {
            abort(403, 'Bu task kaydini guncelleyemezsiniz.');
        }
        $old = (string) $row->status;
        if (! $row->canTransitionTo('todo')) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => 'Bu durumdaki task yeniden açılamaz.']);
        }
        $row->update([
            'status'               => 'todo',
            'completed_at'         => null,
            'cancelled_at'         => null,
            'cancelled_by_user_id' => null,
            'hold_reason'          => null,
        ]);
        TaskActivityLog::record($id, $userId, 'reopened', $old, 'todo');

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'Task tekrar acildi.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user   = $request->user();
        $role   = (string) optional($user)->role;
        $userId = (int) optional($user)->id;
        $row = MarketingTask::query()->findOrFail($id);

        if (! $this->isDeptAdmin($role) && ! $this->isGlobalViewer($role)) {
            abort(403, 'Bu task kaydini silemezsiniz.');
        }
        if (! $this->canManage($request, $row)) {
            abort(403, 'Bu task kaydini silemezsiniz.');
        }
        TaskActivityLog::record($id, $userId, 'deleted');
        $row->delete();

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'Task silindi.');
    }

    public function show(Request $request, int $id): View|RedirectResponse
    {
        $task = MarketingTask::query()
            ->with([
                'assignedUser:id,name,email,role',
                'createdByUser:id,name,email,role',
                'dependsOn:id,title,status',
                'checklists',
                'watchers',
            ])
            ->findOrFail($id);

        if (! $this->canManage($request, $task)) {
            abort(403, 'Bu görevi görüntüleme yetkiniz yok.');
        }

        $statusOptions   = $this->statusOptions();
        $priorityOptions = $this->priorityOptions();
        $deptOptions     = $this->departmentOptions();
        $user            = $request->user();
        $role            = (string) ($user->role ?? '');
        $userId          = (int) $user->id;
        $canEdit         = $this->canManage($request, $task);
        $isGlobalViewer  = $this->isGlobalViewer($role);
        $isDeptAdmin     = $this->isDeptAdmin($role);

        $activityLogs = TaskActivityLog::query()
            ->where('task_id', $id)
            ->with('user:id,name,role')
            ->latest('created_at')
            ->limit(50)
            ->get();

        $baseUrl = '/tasks';
        $dept = trim((string) ($task->department ?? ''));
        if ($dept !== '') {
            $baseUrl = '/tasks/' . $dept;
        }

        $assignees = $this->assignees($role, $userId);

        return view('tasks.show', compact(
            'task', 'statusOptions', 'priorityOptions', 'deptOptions',
            'canEdit', 'isGlobalViewer', 'isDeptAdmin', 'activityLogs', 'baseUrl', 'role', 'userId',
            'assignees'
        ));
    }

    public function detail(Request $request, int $id): JsonResponse
    {
        $task = MarketingTask::query()
            ->with(['assignedUser:id,name,email', 'createdByUser:id,name,email', 'checklists'])
            ->findOrFail($id);

        if (! $this->canManage($request, $task)) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }

        $statusLabels   = $this->statusOptions();
        $priorityLabels = $this->priorityOptions();
        $deptLabels     = $this->departmentOptions();

        return response()->json([
            'id'               => $task->id,
            'title'            => $task->title,
            'description'      => $task->description,
            'status'           => $task->status,
            'status_label'     => $statusLabels[$task->status] ?? $task->status,
            'priority'         => $task->priority,
            'priority_label'   => $priorityLabels[$task->priority] ?? $task->priority,
            'department'       => $task->department,
            'department_label' => $deptLabels[$task->department] ?? $task->department,
            'due_date'         => $task->due_date?->format('d.m.Y'),
            'created_at'       => $task->created_at?->format('d.m.Y H:i'),
            'assignee'         => $task->assignedUser?->name ?? '-',
            'creator'          => $task->createdByUser?->name ?? '-',
            'hold_reason'      => $task->hold_reason,
            'is_recurring'     => (bool) $task->is_recurring,
            'checklist'        => $task->checklists->map(fn ($c) => [
                'title'   => $c->title,
                'is_done' => (bool) $c->is_done,
            ]),
        ]);
    }
}
