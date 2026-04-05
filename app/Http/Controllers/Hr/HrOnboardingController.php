<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\HrOnboardingTask;
use Illuminate\Http\Request;

class HrOnboardingController extends Controller
{
    public function myOnboarding()
    {
        $user = auth()->user();

        // Görev yoksa varsayılanlardan oluştur
        if (HrOnboardingTask::where('user_id', $user->id)->doesntExist()) {
            $cid   = (int) ($user->company_id ?? 0);
            $order = 0;
            foreach (HrOnboardingTask::$defaultTasks as $week => $tasks) {
                foreach ($tasks as $title) {
                    HrOnboardingTask::create([
                        'company_id'  => $cid ?: null,
                        'user_id'     => $user->id,
                        'week'        => (string) $week,
                        'title'       => $title,
                        'sort_order'  => $order++,
                    ]);
                }
            }
        }

        $tasks = HrOnboardingTask::where('user_id', $user->id)
            ->orderBy('week')->orderBy('sort_order')
            ->get();

        $byWeek    = $tasks->groupBy('week');
        $total     = $tasks->count();
        $done      = $tasks->where('is_done', true)->count();
        $pct       = $total > 0 ? (int) round($done / $total * 100) : 0;

        return view('hr.my.onboarding', compact('byWeek', 'total', 'done', 'pct'));
    }

    public function myToggleTask(Request $request, HrOnboardingTask $task)
    {
        abort_if($task->user_id !== auth()->id(), 403);

        $task->update([
            'is_done'      => !$task->is_done,
            'completed_by' => !$task->is_done ? auth()->id() : null,
            'completed_at' => !$task->is_done ? now() : null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'is_done' => $task->fresh()->is_done]);
        }

        return back()->with('status', $task->fresh()->is_done ? 'Görev tamamlandı.' : 'Görev açıldı.');
    }
}
