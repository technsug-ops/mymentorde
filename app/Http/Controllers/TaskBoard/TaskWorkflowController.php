<?php

namespace App\Http\Controllers\TaskBoard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TaskBoard\Concerns\TaskBoardTrait;
use App\Models\MarketingTask;
use App\Models\TaskActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskWorkflowController extends Controller
{
    use TaskBoardTrait;

    public function requestReview(Request $request, int $id): RedirectResponse
    {
        $userId = (int) optional($request->user())->id;
        $row = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $row)) {
            abort(403);
        }
        if (! $row->canTransitionTo('in_review')) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => 'Bu durumdan incelemeye gönderilemez.']);
        }
        $old = (string) $row->status;
        $row->update(['status' => 'in_review', 'review_requested_at' => now(), 'hold_reason' => null]);
        TaskActivityLog::record($id, $userId, 'status_changed', $old, 'in_review');

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'İncelemeye gönderildi.');
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $user   = $request->user();
        $role   = (string) optional($user)->role;
        $userId = (int) optional($user)->id;
        $row = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $row)) {
            abort(403);
        }
        if (! $row->canTransitionTo('done')) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => 'Bu durumdan onaylanamaz.']);
        }
        if ((int) $row->assigned_user_id === $userId && ! $this->isGlobalViewer($role)) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => 'Kendi görevinizi onaylayamazsınız.']);
        }
        $old = (string) $row->status;
        $row->update(['status' => 'done', 'completed_at' => now(), 'hold_reason' => null]);
        TaskActivityLog::record($id, $userId, 'approved', $old, 'done');
        $this->resolveDependents($id, $userId);

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'Görev onaylandı ve tamamlandı.');
    }

    public function requestRevision(Request $request, int $id): RedirectResponse
    {
        $userId = (int) optional($request->user())->id;
        $row = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $row)) {
            abort(403);
        }
        if (! $row->canTransitionTo('in_progress')) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => 'Bu durumdan revizyon istenemez.']);
        }
        $old = (string) $row->status;
        $row->update(['status' => 'in_progress', 'review_requested_at' => null]);
        TaskActivityLog::record($id, $userId, 'revision_requested', $old, 'in_progress');

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'Revizyon istendi.');
    }

    public function hold(Request $request, int $id): RedirectResponse
    {
        $userId = (int) optional($request->user())->id;
        $row = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $row)) {
            abort(403);
        }
        if (! $row->canTransitionTo('on_hold')) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => 'Bu durumdan beklemeye alınamaz.']);
        }
        $data = $request->validate([
            'hold_reason' => ['required', 'string', 'max:255'],
        ]);
        $old = (string) $row->status;
        $row->update(['status' => 'on_hold', 'hold_reason' => trim((string) $data['hold_reason'])]);
        TaskActivityLog::record($id, $userId, 'status_changed', $old, 'on_hold');

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'Görev beklemeye alındı.');
    }

    public function resume(Request $request, int $id): RedirectResponse
    {
        $userId = (int) optional($request->user())->id;
        $row = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $row)) {
            abort(403);
        }
        if (! $row->canTransitionTo('in_progress')) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => 'Bu durumdan devam edilemez.']);
        }
        $old = (string) $row->status;
        $row->update(['status' => 'in_progress', 'hold_reason' => null]);
        TaskActivityLog::record($id, $userId, 'status_changed', $old, 'in_progress');

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'Görev devam ediyor.');
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $userId = (int) optional($request->user())->id;
        $row = MarketingTask::query()->findOrFail($id);
        if (! $this->canManage($request, $row)) {
            abort(403);
        }
        if (! $row->canTransitionTo('cancelled')) {
            return redirect($request->headers->get('referer', '/tasks'))
                ->withErrors(['status' => 'Bu görev iptal edilemez.']);
        }
        $old = (string) $row->status;
        $row->update([
            'status'               => 'cancelled',
            'cancelled_at'         => now(),
            'cancelled_by_user_id' => $userId,
            'hold_reason'          => null,
        ]);
        TaskActivityLog::record($id, $userId, 'status_changed', $old, 'cancelled');

        return redirect($request->headers->get('referer', '/tasks'))->with('status', 'Görev iptal edildi.');
    }
}
