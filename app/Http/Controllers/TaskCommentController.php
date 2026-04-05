<?php

namespace App\Http\Controllers;

use App\Models\MarketingTask;
use App\Models\TaskActivityLog;
use App\Models\TaskComment;
use App\Rules\ValidFileMagicBytes;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskCommentController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request, int $taskId): JsonResponse
    {
        $task = MarketingTask::query()->findOrFail($taskId);
        $user = $request->user();
        $role = (string) ($user->role ?? '');

        $isAdmin = in_array($role, [
            \App\Models\User::ROLE_MANAGER,
            \App\Models\User::ROLE_SYSTEM_ADMIN,
            \App\Models\User::ROLE_OPERATIONS_ADMIN,
            \App\Models\User::ROLE_FINANCE_ADMIN,
            \App\Models\User::ROLE_MARKETING_ADMIN,
            \App\Models\User::ROLE_SALES_ADMIN,
        ], true);

        $comments = TaskComment::query()
            ->where('task_id', $taskId)
            ->with('user:id,name,role')
            ->when(! $isAdmin, fn ($q) => $q->where('is_internal', true))
            ->orderBy('created_at')
            ->get()
            ->map(fn ($c) => [
                'id'              => $c->id,
                'body'            => $c->body,
                'attachment_path' => $c->attachment_path,
                'is_internal'     => $c->is_internal,
                'user_name'       => $c->user?->name ?? 'Sistem',
                'user_role'       => $c->user?->role ?? '',
                'created_at'      => $c->created_at?->format('d.m.Y H:i'),
            ]);

        return response()->json(['comments' => $comments]);
    }

    public function store(Request $request, int $taskId): JsonResponse
    {
        $task   = MarketingTask::query()->findOrFail($taskId);
        $user   = $request->user();
        $userId = (int) $user->id;
        $role   = (string) ($user->role ?? '');

        $data = $request->validate([
            'body'        => ['required', 'string', 'max:3000'],
            'is_internal' => ['nullable', 'boolean'],
            'attachment'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240', new ValidFileMagicBytes],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('task-comments', 'local');
        }

        $isInternal = (bool) ($data['is_internal'] ?? true);
        // Staff sadece internal yorum yapabilir
        $isAdminRole = in_array($role, [
            \App\Models\User::ROLE_MANAGER,
            \App\Models\User::ROLE_SYSTEM_ADMIN,
            \App\Models\User::ROLE_OPERATIONS_ADMIN,
            \App\Models\User::ROLE_FINANCE_ADMIN,
            \App\Models\User::ROLE_MARKETING_ADMIN,
            \App\Models\User::ROLE_SALES_ADMIN,
        ], true);
        if (! $isAdminRole) {
            $isInternal = true;
        }

        $comment = TaskComment::create([
            'task_id'         => $taskId,
            'user_id'         => $userId,
            'body'            => trim($data['body']),
            'attachment_path' => $attachmentPath,
            'is_internal'     => $isInternal,
        ]);

        TaskActivityLog::record($taskId, $userId, 'commented', null, (string) $comment->id);

        // Atanan kişiye bildirim (kendi yorumunu hariç)
        $assignedUserId = (int) ($task->assigned_user_id ?? 0);
        if ($assignedUserId > 0 && $assignedUserId !== $userId) {
            $this->notificationService->send([
                'user_id'      => $assignedUserId,
                'channel'      => 'in_app',
                'category'     => 'task_comment_added',
                'company_id'   => $task->company_id,
                'subject'      => 'Task Yorumu',
                'body'         => 'Task #'.$taskId.' için yeni yorum eklendi.',
                'source_type'  => 'marketing_task',
                'source_id'    => (string) $taskId,
                'triggered_by' => (string) optional(request()->user())->email,
            ]);
        }

        return response()->json([
            'ok'      => true,
            'comment' => [
                'id'              => $comment->id,
                'body'            => $comment->body,
                'attachment_path' => $comment->attachment_path,
                'is_internal'     => $comment->is_internal,
                'user_name'       => $user->name ?? 'Sistem',
                'user_role'       => $role,
                'created_at'      => $comment->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    public function destroy(Request $request, int $taskId, int $commentId): JsonResponse
    {
        $comment = TaskComment::query()->where('task_id', $taskId)->findOrFail($commentId);
        $user    = $request->user();
        $userId  = (int) $user->id;
        $role    = (string) ($user->role ?? '');

        $isAdmin = in_array($role, [
            \App\Models\User::ROLE_MANAGER,
            \App\Models\User::ROLE_SYSTEM_ADMIN,
        ], true);

        // Kendi yorumunu herkes silebilir; başkasının yorumunu sadece manager/system_admin
        if ((int) $comment->user_id !== $userId && ! $isAdmin) {
            abort(403, 'Bu yorumu silme yetkiniz yok.');
        }

        $comment->delete();

        TaskActivityLog::record($taskId, $userId, 'comment_deleted', (string) $commentId, null);

        return response()->json(['ok' => true]);
    }

    public function download(Request $request, int $commentId)
    {
        $comment = TaskComment::query()->findOrFail($commentId);

        // IDOR koruması: görev erişim yetkisi kontrol et
        $user   = $request->user();
        $userId = (int) $user->id;
        $role   = (string) ($user->role ?? '');
        $isAdmin = in_array($role, [
            \App\Models\User::ROLE_MANAGER,
            \App\Models\User::ROLE_SYSTEM_ADMIN,
            \App\Models\User::ROLE_OPERATIONS_ADMIN,
            \App\Models\User::ROLE_FINANCE_ADMIN,
            \App\Models\User::ROLE_MARKETING_ADMIN,
            \App\Models\User::ROLE_SALES_ADMIN,
        ], true);

        if (! $isAdmin) {
            $task = MarketingTask::query()->findOrFail($comment->task_id);
            $mentionedIds = is_array($task->mentioned_user_ids) ? $task->mentioned_user_ids : [];
            $hasAccess = (int) $task->assigned_user_id === $userId
                || (int) $task->created_by_user_id === $userId
                || in_array($userId, $mentionedIds, true);

            if (! $hasAccess) {
                abort(403, 'Bu dosyaya erişim yetkiniz yok.');
            }
        }

        if (! $comment->attachment_path || ! \Storage::disk('local')->exists($comment->attachment_path)) {
            abort(404, 'Dosya bulunamadı.');
        }

        return \Storage::disk('local')->download($comment->attachment_path);
    }
}
