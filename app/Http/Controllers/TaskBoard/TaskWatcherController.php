<?php

namespace App\Http\Controllers\TaskBoard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TaskBoard\Concerns\TaskBoardTrait;
use App\Models\MarketingTask;
use App\Models\TaskWatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskWatcherController extends Controller
{
    use TaskBoardTrait;

    public function watch(Request $request, int $id): JsonResponse
    {
        $task   = MarketingTask::query()->findOrFail($id);
        $userId = (int) $request->user()->id;

        TaskWatcher::query()->firstOrCreate(
            ['task_id' => $task->id, 'user_id' => $userId],
            ['watched_at' => now()],
        );

        return response()->json(['watching' => true, 'count' => $task->watchers()->count()]);
    }

    public function unwatch(Request $request, int $id): JsonResponse
    {
        $task   = MarketingTask::query()->findOrFail($id);
        $userId = (int) $request->user()->id;

        TaskWatcher::query()
            ->where('task_id', $task->id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['watching' => false, 'count' => $task->watchers()->count()]);
    }

    public function watchersList(int $id): JsonResponse
    {
        $task = MarketingTask::query()->with('watcherUsers:id,name,email')->findOrFail($id);

        return response()->json([
            'watchers' => $task->watcherUsers->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
            ]),
            'count' => $task->watcherUsers->count(),
        ]);
    }
}
