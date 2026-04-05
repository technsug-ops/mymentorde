<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\MarketingTask;
use App\Models\TaskAttachment;
use App\Models\TaskWatcher;
use App\Support\FileUploadRules;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $currentUserId = (int) optional($request->user())->id;

        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'assignee' => (int) $request->query('assignee', 0),
        ];

        $rows = MarketingTask::query()
            ->with(['assignedUser:id,name,email', 'createdByUser:id,name,email', 'subtasks', 'checklists', 'watchers'])
            ->whereNull('parent_task_id')
            ->where(function ($q) use ($currentUserId): void {
                // Marketing/Sales departman görevleri
                $q->where(function ($mq): void {
                    $mq->whereHas('assignedUser', function ($uq): void {
                        $uq->whereIn('role', [
                            User::ROLE_MARKETING_ADMIN,
                            User::ROLE_MARKETING_STAFF,
                            User::ROLE_SALES_ADMIN,
                            User::ROLE_SALES_STAFF,
                        ]);
                    })->orWhereHas('createdByUser', function ($uq): void {
                        $uq->whereIn('role', [
                            User::ROLE_MARKETING_ADMIN,
                            User::ROLE_MARKETING_STAFF,
                            User::ROLE_SALES_ADMIN,
                            User::ROLE_SALES_STAFF,
                        ]);
                    })->orWhere(function ($sq): void {
                        $sq->whereNotNull('source_type')
                            ->where('source_type', 'like', 'marketing_%');
                    });
                });
                // Diğer departmanlardan doğrudan atanan veya etiketlenen görevler
                if ($currentUserId > 0) {
                    $q->orWhere('assigned_user_id', $currentUserId)
                      ->orWhereJsonContains('mentioned_user_ids', $currentUserId);
                }
            })
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['priority'] !== '', fn ($q) => $q->where('priority', $filters['priority']))
            ->when($filters['assignee'] > 0, fn ($q) => $q->where('assigned_user_id', $filters['assignee']))
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END ASC")
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->paginate(25);

        // Etiketlenen kullanıcıları toplu yükle
        $allMentionedIds = $rows->getCollection()->flatMap(fn ($r) => $r->mentioned_user_ids ?? [])->unique()->filter()->values()->toArray();
        $mentionedUsersMap = $allMentionedIds
            ? User::whereIn('id', $allMentionedIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        return view('marketing-admin.tasks.index', [
            'pageTitle' => 'To-Do | Marketing',
            'rows' => $rows,
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'assignees' => $this->assignees(),
            'mentionedUsersMap' => $mentionedUsersMap,
            'stats' => [
                'total'       => (int) $this->marketingBoardQuery()->count(),
                'todo'        => (int) $this->marketingBoardQuery()->where('status', 'todo')->count(),
                'in_progress' => (int) $this->marketingBoardQuery()->where('status', 'in_progress')->count(),
                'in_review'   => (int) $this->marketingBoardQuery()->where('status', 'in_review')->count(),
                'on_hold'     => (int) $this->marketingBoardQuery()->whereIn('status', ['on_hold', 'blocked'])->count(),
                'done'        => (int) $this->marketingBoardQuery()->where('status', 'done')->count(),
                'overdue'     => (int) $this->marketingBoardQuery()
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
                'cancelled'   => (int) $this->marketingBoardQuery()->where('status', 'cancelled')->count(),
            ],
            'authUserId'   => (int) $request->user()?->id,
            'authUserRole' => (string) ($request->user()?->role ?? ''),
            'isAdmin'      => in_array((string) ($request->user()?->role ?? ''), [
                User::ROLE_MARKETING_ADMIN, User::ROLE_MANAGER, User::ROLE_SYSTEM_ADMIN,
            ], true),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys($this->statusOptions()))],
            'priority' => ['required', 'string', 'in:'.implode(',', array_keys($this->priorityOptions()))],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'assigned_user_id' => ['nullable', 'integer'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $assignedUserId = (int) ($data['assigned_user_id'] ?? 0);

        MarketingTask::query()->create([
            'title' => trim((string) $data['title']),
            'description' => trim((string) ($data['description'] ?? '')),
            'status' => (string) $data['status'],
            'priority' => (string) $data['priority'],
            'department' => 'marketing',
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'assigned_user_id' => $assignedUserId > 0 ? $assignedUserId : null,
            'created_by_user_id' => (int) optional($request->user())->id ?: null,
            'completed_at' => ((string) $data['status'] === 'done') ? now() : null,
            'progress' => (int) ($data['progress'] ?? 0),
        ]);

        return redirect('/mktg-admin/tasks')->with('status', 'To-do kaydi eklendi.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys($this->statusOptions()))],
            'priority' => ['required', 'string', 'in:'.implode(',', array_keys($this->priorityOptions()))],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'assigned_user_id' => ['nullable', 'integer'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $row = MarketingTask::query()->findOrFail($id);
        $assignedUserId = (int) ($data['assigned_user_id'] ?? 0);
        $newStatus = (string) $data['status'];

        $row->update([
            'title' => trim((string) $data['title']),
            'description' => trim((string) ($data['description'] ?? '')),
            'status' => $newStatus,
            'priority' => (string) $data['priority'],
            'department' => 'marketing',
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'assigned_user_id' => $assignedUserId > 0 ? $assignedUserId : null,
            'completed_at' => $newStatus === 'done' ? ($row->completed_at ?: now()) : null,
            'progress' => $row->subtasks()->count() > 0 ? $row->progress : (int) ($data['progress'] ?? $row->progress),
        ]);

        return redirect('/mktg-admin/tasks')->with('status', 'To-do kaydi guncellendi.');
    }

    public function markDone(int $id): RedirectResponse
    {
        $row = MarketingTask::query()->findOrFail($id);
        $row->update(['status' => 'done', 'completed_at' => now()]);
        return redirect()->back()->with('status', 'Görev tamamlandı.');
    }

    public function reopen(int $id): RedirectResponse
    {
        $row = MarketingTask::query()->findOrFail($id);
        $row->update(['status' => 'todo', 'completed_at' => null]);
        return redirect()->back()->with('status', 'Görev yeniden açıldı.');
    }

    public function requestReview(int $id): RedirectResponse
    {
        $row = MarketingTask::query()->findOrFail($id);
        $row->update(['status' => 'in_review']);
        return redirect()->back()->with('status', 'İncelemeye gönderildi.');
    }

    public function approve(int $id): RedirectResponse
    {
        $row = MarketingTask::query()->findOrFail($id);
        $row->update(['status' => 'done', 'completed_at' => now()]);
        return redirect()->back()->with('status', 'Görev onaylandı ve tamamlandı.');
    }

    public function requestRevision(int $id): RedirectResponse
    {
        $row = MarketingTask::query()->findOrFail($id);
        $row->update(['status' => 'in_progress']);
        return redirect()->back()->with('status', 'Revizyon istendi.');
    }

    public function hold(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['hold_reason' => ['nullable', 'string', 'max:255']]);
        $row  = MarketingTask::query()->findOrFail($id);
        $row->update(['status' => 'on_hold', 'hold_reason' => $data['hold_reason'] ?? null]);
        return redirect()->back()->with('status', 'Görev beklemeye alındı.');
    }

    public function resume(int $id): RedirectResponse
    {
        $row = MarketingTask::query()->findOrFail($id);
        $row->update(['status' => 'in_progress', 'hold_reason' => null]);
        return redirect()->back()->with('status', 'Görev devam ettiriliyor.');
    }

    public function cancel(int $id): RedirectResponse
    {
        $row = MarketingTask::query()->findOrFail($id);
        $row->update(['status' => 'cancelled']);
        return redirect()->back()->with('status', 'Görev iptal edildi.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'task_ids'         => ['required', 'string'],
            'status'           => ['nullable', 'string', 'in:'.implode(',', array_keys($this->statusOptions()))],
            'priority'         => ['nullable', 'string', 'in:'.implode(',', array_keys($this->priorityOptions()))],
            'assigned_user_id' => ['nullable', 'integer'],
        ]);

        $ids = array_filter(array_map('intval', explode(',', (string) $data['task_ids'])));
        if (empty($ids)) {
            return redirect()->back()->with('error', 'Hiçbir görev seçilmedi.');
        }

        $updates = [];
        if (!empty($data['status']))           $updates['status']           = $data['status'];
        if (!empty($data['priority']))         $updates['priority']         = $data['priority'];
        if (!empty($data['assigned_user_id'])) $updates['assigned_user_id'] = (int) $data['assigned_user_id'];

        if (!empty($updates)) {
            if (isset($updates['status']) && $updates['status'] === 'done') {
                $updates['completed_at'] = now();
            }
            MarketingTask::query()->whereIn('id', $ids)->update($updates);
        }

        return redirect()->back()->with('status', count($ids).' görev güncellendi.');
    }

    public function watch(Request $request, int $id): JsonResponse
    {
        $task   = MarketingTask::query()->findOrFail($id);
        $userId = (int) $request->user()?->id;

        $existing = TaskWatcher::query()
            ->where('task_id', $task->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            $watching = false;
        } else {
            TaskWatcher::create(['task_id' => $task->id, 'user_id' => $userId, 'watched_at' => now()]);
            $watching = true;
        }

        $count = TaskWatcher::query()->where('task_id', $task->id)->count();
        return response()->json(['ok' => true, 'watching' => $watching, 'count' => $count]);
    }

    public function detail(int $id): JsonResponse
    {
        $task = MarketingTask::query()
            ->with(['assignedUser:id,name,email', 'createdByUser:id,name,email', 'checklists', 'watchers'])
            ->findOrFail($id);

        $checklist = $task->checklists->map(fn ($cl) => [
            'id'      => $cl->id,
            'title'   => $cl->title,
            'is_done' => (bool) $cl->is_done,
        ])->toArray();

        return response()->json([
            'id'           => $task->id,
            'title'        => $task->title,
            'description'  => $task->description,
            'status'       => $task->status,
            'priority'     => $task->priority,
            'due_date'     => $task->due_date?->format('d.m.Y'),
            'assignee'     => $task->assignedUser?->name,
            'created_by'   => $task->createdByUser?->name,
            'hold_reason'  => $task->hold_reason,
            'progress'     => (int) $task->progress,
            'checklist'    => $checklist,
            'watcher_count'=> $task->watchers->count(),
        ]);
    }

    public function kanban(): JsonResponse
    {
        $tasks = $this->marketingBoardQuery()
            ->with(['assignedUser:id,name,email'])
            ->orderBy('column_order')
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->limit(300)
            ->get(['id', 'title', 'status', 'priority', 'due_date', 'column_order', 'assigned_user_id'])
            ->append([]);

        $grouped = [
            'todo'        => [],
            'in_progress' => [],
            'in_review'   => [],
            'on_hold'     => [],
            'blocked'     => [],
            'done'        => [],
            'cancelled'   => [],
        ];

        foreach ($tasks as $t) {
            $col = array_key_exists($t->status, $grouped) ? $t->status : 'todo';
            $grouped[$col][] = [
                'id'       => $t->id,
                'title'    => $t->title,
                'status'   => $t->status,
                'priority' => $t->priority,
                'due_date' => $t->due_date?->format('Y-m-d'),
                'order'    => (int) $t->column_order,
                'assignee' => $t->assignedUser?->name,
            ];
        }

        return response()->json($grouped);
    }

    public function kanbanUpdate(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status'       => ['required', Rule::in(array_keys($this->statusOptions()))],
            'column_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $row = MarketingTask::query()->findOrFail($id);
        $newStatus = (string) $data['status'];

        $row->update([
            'status'       => $newStatus,
            'column_order' => (int) ($data['column_order'] ?? $row->column_order),
            'completed_at' => $newStatus === 'done' ? ($row->completed_at ?: now()) : null,
        ]);

        return response()->json(['ok' => true, 'id' => $row->id, 'status' => $row->status]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $row = MarketingTask::query()->findOrFail($id);
        $row->delete();

        return redirect('/mktg-admin/tasks')->with('status', 'To-do kaydi silindi.');
    }

    // ─── Sub-task endpoints ───────────────────────────────────────────────────

    public function subtaskStore(Request $request, int $id): JsonResponse
    {
        $parent = MarketingTask::query()->whereNull('parent_task_id')->findOrFail($id);
        $data   = $request->validate(['title' => ['required', 'string', 'max:190']]);

        $sub = MarketingTask::create([
            'title'              => trim((string) $data['title']),
            'status'             => 'todo',
            'priority'           => $parent->priority,
            'department'         => $parent->department ?? 'marketing',
            'parent_task_id'     => $parent->id,
            'company_id'         => $parent->company_id,
            'created_by_user_id' => (int) optional($request->user())->id ?: null,
        ]);

        $this->syncProgress($parent);

        return response()->json(['ok' => true, 'subtask' => [
            'id' => $sub->id, 'title' => $sub->title, 'status' => $sub->status,
        ]]);
    }

    public function subtaskToggle(int $id, int $subId): JsonResponse
    {
        $sub       = MarketingTask::query()->where('parent_task_id', $id)->findOrFail($subId);
        $newStatus = $sub->status === 'done' ? 'todo' : 'done';
        $sub->update(['status' => $newStatus, 'completed_at' => $newStatus === 'done' ? now() : null]);

        $parent = MarketingTask::find($id);
        if ($parent) {
            $this->syncProgress($parent);
        }

        return response()->json(['ok' => true, 'status' => $newStatus]);
    }

    public function subtaskDelete(int $id, int $subId): JsonResponse
    {
        $sub = MarketingTask::query()->where('parent_task_id', $id)->findOrFail($subId);
        $sub->delete();

        $parent = MarketingTask::find($id);
        if ($parent) {
            $this->syncProgress($parent);
        }

        return response()->json(['ok' => true]);
    }

    private function syncProgress(MarketingTask $parent): void
    {
        $total = $parent->subtasks()->count();
        if ($total === 0) {
            return;
        }
        $done = $parent->subtasks()->where('status', 'done')->count();
        $parent->update(['progress' => (int) round($done / $total * 100)]);
    }

    // ─── Gantt endpoint ──────────────────────────────────────────────────────

    public function gantt(Request $request): JsonResponse
    {
        $currentUserId = (int) optional($request->user())->id;
        $rangeStart = now()->subDays(3)->toDateString();
        $rangeEnd   = now()->addDays(60)->toDateString();

        $tasks = MarketingTask::query()
            ->with(['assignedUser:id,name'])
            ->where(function ($q) use ($currentUserId): void {
                $q->where(function ($mq): void {
                    $mq->whereHas('assignedUser', function ($uq): void {
                        $uq->whereIn('role', [
                            User::ROLE_MARKETING_ADMIN,
                            User::ROLE_MARKETING_STAFF,
                            User::ROLE_SALES_ADMIN,
                            User::ROLE_SALES_STAFF,
                        ]);
                    })->orWhereHas('createdByUser', function ($uq): void {
                        $uq->whereIn('role', [
                            User::ROLE_MARKETING_ADMIN,
                            User::ROLE_MARKETING_STAFF,
                            User::ROLE_SALES_ADMIN,
                            User::ROLE_SALES_STAFF,
                        ]);
                    })->orWhere(function ($sq): void {
                        $sq->whereNotNull('source_type')
                            ->where('source_type', 'like', 'marketing_%');
                    });
                });
                if ($currentUserId > 0) {
                    $q->orWhere('assigned_user_id', $currentUserId)
                      ->orWhereJsonContains('mentioned_user_ids', $currentUserId);
                }
            })
            ->where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->limit(200)
            ->get();

        $data = $tasks->map(fn ($t) => [
            'id'       => $t->id,
            'title'    => $t->title,
            'priority' => $t->priority,
            'status'   => $t->status,
            'start'    => $t->start_date?->format('Y-m-d') ?? now()->toDateString(),
            'end'      => $t->due_date->format('Y-m-d'),
            'assignee' => $t->assignedUser?->name,
        ]);

        return response()->json([
            'tasks'       => $data,
            'range_start' => $rangeStart,
            'range_end'   => $rangeEnd,
            'today'       => now()->toDateString(),
        ]);
    }

    // ─── Mention / Tag endpoints ──────────────────────────────────────────────

    public function allUsers(Request $request): JsonResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $users = User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return response()->json(['users' => $users]);
    }

    public function mention(Request $request, int $id): JsonResponse
    {
        $task = MarketingTask::query()->findOrFail($id);
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'action'  => ['required', 'in:add,remove'],
        ]);

        $mentionedIds = $task->mentioned_user_ids ?? [];
        $uid = (int) $data['user_id'];

        if ($data['action'] === 'add') {
            if (! in_array($uid, $mentionedIds, true)) {
                $mentionedIds[] = $uid;
            }
        } else {
            $mentionedIds = array_values(array_filter($mentionedIds, fn ($mid) => $mid !== $uid));
        }

        $task->update(['mentioned_user_ids' => $mentionedIds ?: null]);

        $mentionedUsers = $mentionedIds
            ? User::whereIn('id', $mentionedIds)->get(['id', 'name', 'email'])
            : collect();

        return response()->json(['ok' => true, 'mentioned_users' => $mentionedUsers]);
    }

    // ─── Attachment endpoints ────────────────────────────────────────────────

    public function attachmentIndex(int $taskId): JsonResponse
    {
        $task = MarketingTask::query()->findOrFail($taskId);
        $attachments = TaskAttachment::query()
            ->where('task_id', $task->id)
            ->orderBy('id')
            ->get()
            ->map(fn (TaskAttachment $a) => $a->toFrontend());

        return response()->json(['attachments' => $attachments]);
    }

    public function attachmentStore(Request $request, int $taskId): JsonResponse
    {
        $task = MarketingTask::query()->findOrFail($taskId);
        $userId = (int) optional($request->user())->id;

        // Link tipi
        if ($request->input('type') === 'link') {
            $data = $request->validate(['url' => ['required', 'url', 'max:2048']]);
            $att = TaskAttachment::create([
                'task_id'         => $task->id,
                'user_id'         => $userId ?: null,
                'attachment_type' => 'link',
                'url'             => $data['url'],
            ]);
            return response()->json(['ok' => true, 'attachment' => $att->toFrontend()]);
        }

        // Dosya tipi
        $request->validate(['file' => FileUploadRules::media()]);

        $file = $request->file('file');
        $mime = (string) $file->getMimeType();
        $type = match (true) {
            str_starts_with($mime, 'image/')       => 'image',
            str_starts_with($mime, 'video/')       => 'video',
            $mime === 'application/pdf'            => 'pdf',
            default                                 => 'file',
        };

        $path = $file->store("task-attachments/{$task->id}", 'public');

        $att = TaskAttachment::create([
            'task_id'         => $task->id,
            'user_id'         => $userId ?: null,
            'attachment_type' => $type,
            'file_path'       => $path,
            'original_name'   => $file->getClientOriginalName(),
            'mime_type'       => $mime,
            'file_size'       => $file->getSize(),
            'url'             => Storage::disk('public')->url($path),
        ]);

        return response()->json(['ok' => true, 'attachment' => $att->toFrontend()]);
    }

    public function attachmentDelete(Request $request, int $attachId): JsonResponse
    {
        $att = TaskAttachment::query()->findOrFail($attachId);
        $userId = (int) optional($request->user())->id;

        // Sadece yükleyen silebilir (veya admin)
        $role = (string) optional($request->user())->role;
        $isAdmin = in_array($role, [User::ROLE_MANAGER, User::ROLE_SYSTEM_ADMIN,
            User::ROLE_MARKETING_ADMIN, User::ROLE_SALES_ADMIN], true);

        if (!$isAdmin && (int) $att->user_id !== $userId) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }

        if ($att->file_path) {
            Storage::disk('public')->delete($att->file_path);
        }
        $att->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return [
            'todo'        => 'Yapılacak',
            'in_progress' => 'Devam Ediyor',
            'in_review'   => 'İncelemede',
            'on_hold'     => 'Beklemede',
            'blocked'     => 'Bloke',
            'done'        => 'Tamamlandı',
            'cancelled'   => 'İptal',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function priorityOptions(): array
    {
        return [
            'low' => 'Dusuk',
            'normal' => 'Normal',
            'high' => 'Yuksek',
            'urgent' => 'Acil',
        ];
    }

    private function assignees()
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $actorRole = (string) (auth()->user()->role ?? '');

        // marketing_staff yalnızca kendi seviyesindeki rollere atayabilir;
        // marketing_admin ve üstü manager/system rollerini de görebilir.
        $staffOnlyRoles = [
            User::ROLE_MARKETING_ADMIN,
            User::ROLE_MARKETING_STAFF,
            User::ROLE_SALES_ADMIN,
            User::ROLE_SALES_STAFF,
        ];

        $allowedRoles = $actorRole === User::ROLE_MARKETING_STAFF
            ? $staffOnlyRoles
            : User::MARKETING_ACCESS_ROLES;

        return User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', $allowedRoles)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }

    private function marketingBoardQuery()
    {
        return MarketingTask::query()->whereNull('parent_task_id')->where(function ($q): void {
            $q->whereHas('assignedUser', function ($uq): void {
                $uq->whereIn('role', [
                    User::ROLE_MARKETING_ADMIN,
                    User::ROLE_MARKETING_STAFF,
                    User::ROLE_SALES_ADMIN,
                    User::ROLE_SALES_STAFF,
                ]);
            })->orWhereHas('createdByUser', function ($uq): void {
                $uq->whereIn('role', [
                    User::ROLE_MARKETING_ADMIN,
                    User::ROLE_MARKETING_STAFF,
                    User::ROLE_SALES_ADMIN,
                    User::ROLE_SALES_STAFF,
                ]);
            })->orWhere(function ($sq): void {
                $sq->whereNotNull('source_type')
                    ->where('source_type', 'like', 'marketing_%');
            });
        });
    }
}
