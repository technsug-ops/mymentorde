<?php

namespace App\Http\Controllers\TaskBoard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TaskBoard\Concerns\TaskBoardTrait;
use App\Models\MarketingTask;
use App\Models\TaskActivityLog;
use App\Models\TaskChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskChecklistController extends Controller
{
    use TaskBoardTrait;

    public function checklistStore(Request $request, int $id): JsonResponse
    {
        $task = MarketingTask::query()->findOrFail($id);
        if (! $this->canViewTask($request, $task)) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }
        if ((int) $task->checklist_total >= 20) {
            return response()->json(['error' => 'Bir task\'ta en fazla 20 checklist maddesi olabilir.'], 422);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $maxOrder = TaskChecklist::query()->where('task_id', $id)->max('sort_order') ?? -1;
        $item = TaskChecklist::query()->create([
            'task_id'    => $id,
            'title'      => trim((string) $data['title']),
            'sort_order' => $maxOrder + 1,
        ]);

        $task->increment('checklist_total');

        return response()->json([
            'id'         => $item->id,
            'title'      => $item->title,
            'is_done'    => false,
            'sort_order' => $item->sort_order,
            'progress'   => $task->fresh()->checklist_progress,
        ], 201);
    }

    public function checklistToggle(Request $request, int $id, int $itemId): JsonResponse
    {
        $task = MarketingTask::query()->findOrFail($id);
        $item = TaskChecklist::query()->where('task_id', $id)->findOrFail($itemId);

        $currentCompanyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        if ($currentCompanyId > 0 && (int) ($task->company_id ?? 0) !== $currentCompanyId) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }

        $userId  = (int) optional($request->user())->id;
        $newDone = ! $item->is_done;

        $item->update([
            'is_done'         => $newDone,
            'done_by_user_id' => $newDone ? $userId : null,
            'done_at'         => $newDone ? now() : null,
        ]);

        $doneCount = TaskChecklist::query()->where('task_id', $id)->where('is_done', true)->count();
        $task->update(['checklist_done' => $doneCount]);

        TaskActivityLog::record($id, $userId, 'checklist_toggled', null, $newDone ? 'done' : 'undone');

        return response()->json([
            'is_done'  => $newDone,
            'progress' => $task->fresh()->checklist_progress,
            'done'     => $doneCount,
            'total'    => (int) $task->checklist_total,
        ]);
    }

    public function checklistDestroy(Request $request, int $id, int $itemId): JsonResponse
    {
        $task = MarketingTask::query()->findOrFail($id);
        if (! $this->canViewTask($request, $task)) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }

        $item = TaskChecklist::query()->where('task_id', $id)->findOrFail($itemId);
        $wasDone = (bool) $item->is_done;
        $item->delete();

        $task->decrement('checklist_total');
        if ($wasDone) {
            $task->decrement('checklist_done');
        }

        $task->refresh();

        return response()->json([
            'progress' => $task->checklist_progress,
            'done'     => (int) $task->checklist_done,
            'total'    => (int) $task->checklist_total,
        ]);
    }

    public function checklistReorder(Request $request, int $id): JsonResponse
    {
        $task = MarketingTask::query()->findOrFail($id);
        if (! $this->canViewTask($request, $task)) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }

        $data = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($data['order'] as $sortOrder => $itemId) {
            TaskChecklist::query()
                ->where('task_id', $id)
                ->where('id', (int) $itemId)
                ->update(['sort_order' => (int) $sortOrder]);
        }

        return response()->json(['ok' => true]);
    }
}
