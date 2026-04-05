<?php

namespace App\Http\Controllers\TaskBoard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TaskBoard\Concerns\TaskBoardTrait;
use App\Models\MarketingTask;
use App\Models\TaskTimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskTimeController extends Controller
{
    use TaskBoardTrait;

    public function timeStart(Request $request, int $id): JsonResponse
    {
        $task = MarketingTask::findOrFail($id);
        abort_if(!$this->canViewTask($request, $task), 403);

        $userId = (int) $request->user()->id;

        $existing = TaskTimeEntry::where('task_id', $id)
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->first();

        if ($existing) {
            return response()->json(['ok' => false, 'error' => 'Zaten çalışan bir sayaç var.', 'entry_id' => $existing->id], 422);
        }

        $entry = TaskTimeEntry::create([
            'task_id'    => $id,
            'user_id'    => $userId,
            'started_at' => now(),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true, 'entry_id' => $entry->id, 'started_at' => $entry->started_at]);
    }

    public function timeStop(Request $request, int $id): JsonResponse
    {
        $task = MarketingTask::findOrFail($id);
        abort_if(!$this->canViewTask($request, $task), 403);

        $userId = (int) $request->user()->id;

        $entry = TaskTimeEntry::where('task_id', $id)
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->firstOrFail();

        $minutes = (int) $entry->started_at->diffInMinutes(now());
        $entry->update([
            'ended_at'         => now(),
            'duration_minutes' => $minutes,
        ]);

        return response()->json([
            'ok'               => true,
            'duration_minutes' => $minutes,
            'duration_hours'   => round($minutes / 60, 2),
        ]);
    }

    public function timeList(Request $request, int $id): JsonResponse
    {
        $task = MarketingTask::findOrFail($id);
        abort_if(!$this->canViewTask($request, $task), 403);

        $entries = TaskTimeEntry::where('task_id', $id)
            ->with('user:id,name,role')
            ->orderByDesc('started_at')
            ->get()
            ->map(fn ($e) => [
                'id'               => $e->id,
                'user'             => $e->user?->name,
                'started_at'       => $e->started_at?->format('d.m.Y H:i'),
                'ended_at'         => $e->ended_at?->format('d.m.Y H:i'),
                'duration_minutes' => $e->duration_minutes,
                'duration_hours'   => $e->duration_minutes ? round($e->duration_minutes / 60, 2) : null,
                'note'             => $e->note,
                'running'          => $e->ended_at === null,
            ]);

        $totalMinutes = $entries->whereNotNull('duration_minutes')->sum('duration_minutes');

        return response()->json([
            'entries'       => $entries,
            'total_minutes' => $totalMinutes,
            'total_hours'   => round($totalMinutes / 60, 2),
        ]);
    }
}
