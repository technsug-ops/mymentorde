<?php

namespace App\Http\Controllers\TaskBoard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TaskBoard\Concerns\TaskBoardTrait;
use App\Models\MarketingTask;
use App\Models\TaskActivityLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskAnalyticsController extends Controller
{
    use TaskBoardTrait;

    public function kanbanUpdate(Request $request, int $id): JsonResponse
    {
        $row = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $row)) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }
        $data = $request->validate([
            'status'       => ['required', 'string', 'in:'.implode(',', array_keys($this->statusOptions()))],
            'column_order' => ['nullable', 'integer'],
        ]);
        $old    = (string) $row->status;
        $new    = (string) $data['status'];
        $userId = (int) optional($request->user())->id;

        if ($old !== $new && ! $row->canTransitionTo($new)) {
            return response()->json(['error' => "{$old} → {$new} geçişi geçerli değil."], 422);
        }

        $statusPayload = $this->buildStatusPayload($row, $new, $userId);
        $row->update(array_merge([
            'status'       => $new,
            'column_order' => (int) ($data['column_order'] ?? 0),
        ], $statusPayload));

        if ($old !== $new) {
            TaskActivityLog::record($id, $userId, 'status_changed', $old, $new);
            if ($new === 'done') {
                $this->resolveDependents($id, $userId);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function kanbanData(Request $request): JsonResponse
    {
        $user           = $request->user();
        $role           = (string) ($user->role ?? '');
        $userId         = (int) $user->id;
        $isGlobalViewer = $this->isGlobalViewer($role);
        $isDeptAdmin    = $this->isDeptAdmin($role);
        $roleScopedDep  = $this->resolveScopedDepartmentForRole($role);
        $department     = trim((string) $request->query('department', ''));

        $tasks = MarketingTask::query()
            ->with(['assignedUser:id,name,email'])
            ->when(! $isGlobalViewer, function ($q) use ($isDeptAdmin, $roleScopedDep, $userId): void {
                if ($isDeptAdmin && $roleScopedDep !== null) {
                    $q->where('department', $roleScopedDep);
                } elseif (! $isDeptAdmin) {
                    $q->where(function ($sub) use ($userId): void {
                        $sub->where('assigned_user_id', $userId)->orWhere('created_by_user_id', $userId);
                    });
                }
            })
            ->when($department !== '', fn ($q) => $q->where('department', $department))
            ->orderBy('column_order')
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->limit(300)
            ->get(['id', 'title', 'status', 'priority', 'due_date', 'column_order', 'assigned_user_id', 'process_type']);

        $processLabels = MarketingTask::PROCESS_TYPES;
        $grouped = ['todo' => [], 'in_progress' => [], 'in_review' => [], 'on_hold' => [], 'blocked' => [], 'done' => [], 'cancelled' => []];
        foreach ($tasks as $t) {
            $col = array_key_exists($t->status, $grouped) ? $t->status : 'todo';
            $grouped[$col][] = [
                'id'                 => $t->id,
                'title'              => $t->title,
                'status'             => $t->status,
                'priority'           => $t->priority,
                'due_date'           => $t->due_date?->format('Y-m-d'),
                'order'              => (int) $t->column_order,
                'assignee'           => $t->assignedUser?->name,
                'process_type'       => $t->process_type,
                'process_type_label' => $t->process_type ? ($processLabels[$t->process_type] ?? $t->process_type) : null,
            ];
        }

        return response()->json(['columns' => $grouped]);
    }

    public function gantt(Request $request): JsonResponse
    {
        $user           = $request->user();
        $role           = (string) ($user->role ?? '');
        $userId         = (int) $user->id;
        $isGlobalViewer = $this->isGlobalViewer($role);
        $isDeptAdmin    = $this->isDeptAdmin($role);
        $roleScopedDep  = $this->resolveScopedDepartmentForRole($role);
        $department     = trim((string) $request->query('department', ''));

        $q = MarketingTask::query()
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_date')
            ->when(! $isGlobalViewer, function ($q) use ($isDeptAdmin, $roleScopedDep, $userId): void {
                if ($isDeptAdmin && $roleScopedDep !== null) {
                    $q->where('department', $roleScopedDep);
                } elseif (! $isDeptAdmin) {
                    $q->where(function ($sub) use ($userId): void {
                        $sub->where('assigned_user_id', $userId)->orWhere('created_by_user_id', $userId);
                    });
                }
            })
            ->when($department !== '', fn ($q) => $q->where('department', $department))
            ->orderBy('due_date')
            ->limit(150)
            ->get(['id', 'title', 'status', 'priority', 'start_date', 'due_date', 'assigned_user_id']);

        $today = now()->toDateString();
        $tasks = $q->map(fn ($t) => [
            'id'       => $t->id,
            'title'    => $t->title,
            'status'   => $t->status,
            'priority' => $t->priority,
            'start'    => $t->start_date ? $t->start_date->toDateString() : $today,
            'end'      => $t->due_date->toDateString(),
        ]);

        $allDates   = $tasks->flatMap(fn ($t) => [$t['start'], $t['end']]);
        $rangeStart = Carbon::parse($allDates->min() ?? $today)->subDays(3)->toDateString();
        $rangeEnd   = Carbon::parse($allDates->max() ?? $today)->addDays(3)->toDateString();

        return response()->json([
            'tasks'       => $tasks->values(),
            'today'       => $today,
            'range_start' => $rangeStart,
            'range_end'   => $rangeEnd,
        ]);
    }

    public function myMetrics(Request $request): JsonResponse
    {
        $userId = (int) optional($request->user())->id;
        $base = MarketingTask::query()->where(function ($q) use ($userId): void {
            $q->where('assigned_user_id', $userId)->orWhere('created_by_user_id', $userId);
        });

        return response()->json([
            'open'      => (int) (clone $base)->whereNotIn('status', ['done', 'cancelled'])->count(),
            'due_today' => (int) (clone $base)->whereNotIn('status', ['done', 'cancelled'])->whereDate('due_date', now()->toDateString())->count(),
            'overdue'   => (int) (clone $base)->whereNotIn('status', ['done', 'cancelled'])->whereDate('due_date', '<', now()->toDateString())->count(),
            'done_30d'  => (int) (clone $base)->where('status', 'done')->where('completed_at', '>=', now()->subDays(30))->count(),
        ]);
    }

    public function activityLog(Request $request, int $id): JsonResponse
    {
        $task = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $task)) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }

        $logs = TaskActivityLog::query()
            ->where('task_id', $id)
            ->with('user:id,name,role')
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($l) => [
                'action'     => $l->action,
                'old_value'  => $l->old_value,
                'new_value'  => $l->new_value,
                'user_name'  => $l->user?->name ?? 'Sistem',
                'user_role'  => $l->user?->role ?? '',
                'created_at' => $l->created_at?->format('d.m.Y H:i'),
            ]);

        return response()->json(['logs' => $logs]);
    }

    public function taskReport(Request $request): JsonResponse
    {
        $start     = Carbon::parse($request->query('start', now()->startOfMonth()->toDateString()));
        $end       = Carbon::parse($request->query('end', now()->endOfMonth()->toDateString()));
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $tasks = MarketingTask::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->get();

        $byDepartment = $tasks->groupBy('department')->map(fn ($rows, $dept) => [
            'department'       => $dept,
            'total'            => $rows->count(),
            'done'             => $rows->where('status', 'done')->count(),
            'overdue'          => $rows->where('status', '!=', 'done')
                ->filter(fn ($t) => $t->due_date && Carbon::parse($t->due_date)->lt(today()))
                ->count(),
            'avg_actual_hours' => round((float) $rows->whereNotNull('actual_hours')->avg('actual_hours'), 1),
        ])->values();

        $byUser = $tasks->groupBy('assigned_user_id')->map(fn ($rows, $uid) => [
            'user_id'          => $uid,
            'total'            => $rows->count(),
            'done'             => $rows->where('status', 'done')->count(),
            'avg_actual_hours' => round((float) $rows->whereNotNull('actual_hours')->avg('actual_hours'), 1),
        ])->values();

        return response()->json([
            'period'        => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'by_department' => $byDepartment,
            'by_user'       => $byUser,
            'summary'       => [
                'total'    => $tasks->count(),
                'done'     => $tasks->where('status', 'done')->count(),
                'overdue'  => $tasks->filter(fn ($t) => $t->status !== 'done' && $t->due_date && Carbon::parse($t->due_date)->lt(today()))->count(),
                'accuracy' => $this->estimateAccuracy($tasks),
            ],
        ]);
    }
}
