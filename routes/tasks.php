<?php

use App\Http\Controllers\InternalMessagingController;
use App\Http\Controllers\ManagerRequestController;
use App\Http\Controllers\MessageCenterController;
use App\Http\Controllers\TaskBoardController;
use App\Http\Controllers\TaskBoard\TaskAnalyticsController;
use App\Http\Controllers\TaskBoard\TaskChecklistController;
use App\Http\Controllers\TaskBoard\TaskTimeController;
use App\Http\Controllers\TaskBoard\TaskWatcherController;
use App\Http\Controllers\TaskBoard\TaskWorkflowController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TicketCenterController;
use App\Http\Controllers\UnifiedMessagingHubController;
use Illuminate\Support\Facades\Route;

Route::middleware(['company.context', 'auth', 'task.access'])->group(function (): void {
    // ── Task Board — Core CRUD ────────────────────────────────────────────────
    Route::get('/tasks', [TaskBoardController::class, 'index']);
    Route::get('/tasks/{department}', [TaskBoardController::class, 'index'])
        ->where('department', 'operations|finance|advisory|marketing|system');
    Route::post('/tasks', [TaskBoardController::class, 'store']);
    Route::put('/tasks/{id}', [TaskBoardController::class, 'update']);
    Route::post('/tasks/bulk-update', [TaskBoardController::class, 'bulkUpdate']);
    Route::post('/tasks/{id}/done', [TaskBoardController::class, 'markDone']);
    Route::post('/tasks/{id}/reopen', [TaskBoardController::class, 'reopen']);
    Route::delete('/tasks/{id}', [TaskBoardController::class, 'destroy']);
    Route::get('/tasks/{id}/show', [TaskBoardController::class, 'show']);
    Route::get('/tasks/{id}/detail', [TaskBoardController::class, 'detail']);

    // ── State Machine Actions (TaskWorkflowController) ────────────────────────
    Route::post('/tasks/{id}/request-review', [TaskWorkflowController::class, 'requestReview']);
    Route::post('/tasks/{id}/approve', [TaskWorkflowController::class, 'approve']);
    Route::post('/tasks/{id}/request-revision', [TaskWorkflowController::class, 'requestRevision']);
    Route::post('/tasks/{id}/hold', [TaskWorkflowController::class, 'hold']);
    Route::post('/tasks/{id}/resume', [TaskWorkflowController::class, 'resume']);
    Route::post('/tasks/{id}/cancel', [TaskWorkflowController::class, 'cancel']);

    // ── Watchers (TaskWatcherController) ─────────────────────────────────────
    Route::post('/tasks/{id}/watch', [TaskWatcherController::class, 'watch']);
    Route::delete('/tasks/{id}/watch', [TaskWatcherController::class, 'unwatch']);
    Route::get('/tasks/{id}/watchers', [TaskWatcherController::class, 'watchersList']);

    // ── Checklist (TaskChecklistController) — reorder önce ───────────────────
    Route::patch('/tasks/{id}/checklist/reorder', [TaskChecklistController::class, 'checklistReorder']);
    Route::post('/tasks/{id}/checklist', [TaskChecklistController::class, 'checklistStore']);
    Route::patch('/tasks/{id}/checklist/{itemId}/toggle', [TaskChecklistController::class, 'checklistToggle']);
    Route::delete('/tasks/{id}/checklist/{itemId}', [TaskChecklistController::class, 'checklistDestroy']);

    // ── Analytics & Views (TaskAnalyticsController) ───────────────────────────
    Route::patch('/tasks/{id}/kanban', [TaskAnalyticsController::class, 'kanbanUpdate']);
    Route::get('/tasks/{id}/activity', [TaskAnalyticsController::class, 'activityLog']);
    Route::get('/tasks/metrics/my', [TaskAnalyticsController::class, 'myMetrics']);
    Route::get('/tasks/gantt/data', [TaskAnalyticsController::class, 'gantt']);
    Route::get('/tasks/kanban/data', [TaskAnalyticsController::class, 'kanbanData']);
    Route::get('/tasks/report/data', [TaskAnalyticsController::class, 'taskReport'])->name('tasks.report');

    // ── Comments ──────────────────────────────────────────────────────────────
    Route::get('/tasks/{taskId}/comments', [TaskCommentController::class, 'index']);
    Route::post('/tasks/{taskId}/comments', [TaskCommentController::class, 'store'])->middleware('throttle:30,1');
    Route::delete('/tasks/{taskId}/comments/{commentId}', [TaskCommentController::class, 'destroy']);
    Route::get('/tasks/{taskId}/comments/{commentId}/download', [TaskCommentController::class, 'download']);
    Route::get('/task-comment-file/{commentId}', [TaskCommentController::class, 'download']); // legacy alias

    // ── Time Tracking (TaskTimeController) ───────────────────────────────────
    Route::post('/tasks/{id}/time/start', [TaskTimeController::class, 'timeStart'])->name('tasks.time.start');
    Route::post('/tasks/{id}/time/stop', [TaskTimeController::class, 'timeStop'])->name('tasks.time.stop');
    Route::get('/tasks/{id}/time', [TaskTimeController::class, 'timeList'])->name('tasks.time.list');

    // ── Görev Şablonu ─────────────────────────────────────────────────────────
    Route::post('/task-templates/{templateId}/apply', [\App\Http\Controllers\Api\TaskTemplateController::class, 'apply'])->name('task-templates.apply');

    // ── Ticket Center ─────────────────────────────────────────────────────────
    Route::get('/tickets-center', [TicketCenterController::class, 'index']);
    Route::get('/tickets-center/{department}', [TicketCenterController::class, 'index'])
        ->where('department', 'operations|finance|advisory|marketing|system');
    Route::post('/tickets-center/bulk-route', [TicketCenterController::class, 'bulkRoute']);
    Route::post('/tickets-center/bulk-status', [TicketCenterController::class, 'bulkStatus']);
    Route::post('/tickets-center/{ticket}/route', [TicketCenterController::class, 'routeTicket']);
    Route::post('/tickets-center/{ticket}/convert-to-dm', [TicketCenterController::class, 'convertToDm'])->name('tickets.center.convert-dm');

    // ── Manager Requests ──────────────────────────────────────────────────────
    Route::get('/manager/requests', [ManagerRequestController::class, 'index'])->name('manager.requests.index');
    Route::post('/manager/requests', [ManagerRequestController::class, 'store'])->name('manager.requests.store');
    Route::post('/manager/requests/{managerRequest}/status', [ManagerRequestController::class, 'updateStatus'])->name('manager.requests.update-status');

    // ── Message Center ────────────────────────────────────────────────────────
    Route::get('/messages-center', [MessageCenterController::class, 'index'])->name('messages.center');
    Route::get('/messages-center/{department}', [MessageCenterController::class, 'index'])
        ->where('department', 'operations|finance|advisory|marketing|system')
        ->name('messages.center.department');
    Route::post('/messages-center/bulk-update', [MessageCenterController::class, 'bulkUpdate'])->name('messages.center.bulk-update');
    Route::post('/messages-center/bulk-mark-read', [MessageCenterController::class, 'bulkMarkRead'])->name('messages.center.bulk-mark-read');
    Route::post('/messages-center/{thread}/assign-advisor', [MessageCenterController::class, 'assignAdvisor'])->name('messages.center.assign-advisor');
    Route::post('/messages-center/{thread}/convert-to-ticket', [MessageCenterController::class, 'convertToTicket'])->name('messages.center.convert-ticket');
    Route::post('/messages-center/{thread}/status', [MessageCenterController::class, 'updateStatus'])->name('messages.center.status');
    Route::post('/messages-center/{thread}/send', [MessageCenterController::class, 'send'])->name('messages.center.send');

    // ── Dahili Mesajlaşma (Unified IM Hub) ────────────────────────────────────
    Route::get('/im', [UnifiedMessagingHubController::class, 'index'])->name('im.index');
    Route::post('/im/dm/{targetUserId}', [InternalMessagingController::class, 'dmStart'])->name('im.dm.start');
    Route::post('/im/group', [InternalMessagingController::class, 'groupCreate'])->name('im.group.create');
    Route::post('/im/conversations/{convId}/send', [InternalMessagingController::class, 'send'])->middleware('throttle:30,1')->name('im.send');
    Route::post('/im/conversations/{convId}/read', [InternalMessagingController::class, 'read'])->name('im.read');
    Route::post('/im/conversations/{convId}/mute', [InternalMessagingController::class, 'mute'])->name('im.mute');
    Route::post('/im/conversations/{convId}/pin', [InternalMessagingController::class, 'pin'])->name('im.pin');
    Route::delete('/im/messages/{msgId}', [InternalMessagingController::class, 'deleteMessage'])->name('im.message.delete');
    Route::post('/im/messages/{msgId}', [InternalMessagingController::class, 'deleteMessage'])->name('im.message.delete.post');
    Route::get('/im/messages/{msgId}/download', [InternalMessagingController::class, 'download'])->name('im.message.download');
    Route::post('/im/conversations/{convId}/members', [InternalMessagingController::class, 'groupAddMember'])->name('im.group.member.add');
    Route::post('/im/conversations/{convId}/members/{targetUserId}/remove', [InternalMessagingController::class, 'groupRemoveMember'])->name('im.group.member.remove');
    Route::get('/im/conversations/{convId}/poll', [InternalMessagingController::class, 'poll'])->name('im.poll');
    Route::post('/im/conversations/{convId}/archive', [InternalMessagingController::class, 'archive'])->name('im.archive');
    Route::post('/im/conversations/{convId}/unarchive', [InternalMessagingController::class, 'unarchive'])->name('im.unarchive');
    Route::delete('/im/conversations/{convId}', [InternalMessagingController::class, 'destroy'])->name('im.destroy');
    Route::post('/im/conversations/{convId}/destroy', [InternalMessagingController::class, 'destroy'])->name('im.destroy.post');
    Route::post('/im/conversations/{convId}/members/{targetUserId}/promote', [InternalMessagingController::class, 'promoteMember'])->name('im.member.promote');
    Route::post('/im/conversations/{convId}/members/{targetUserId}/demote', [InternalMessagingController::class, 'demoteMember'])->name('im.member.demote');
    Route::get('/im/unread-count', [InternalMessagingController::class, 'unreadCount'])->name('im.unread.count');
    Route::get('/im/search', [InternalMessagingController::class, 'search'])->name('im.search');
    Route::put('/im/messages/{msgId}', [InternalMessagingController::class, 'editMessage'])->name('im.message.edit');
    Route::post('/im/messages/{msgId}/edit', [InternalMessagingController::class, 'editMessage'])->name('im.message.edit.post');
    Route::post('/im/messages/{msgId}/react', [InternalMessagingController::class, 'react'])->name('im.message.react');
    Route::delete('/im/messages/{msgId}/react/{emoji}', [InternalMessagingController::class, 'removeReaction'])->name('im.message.react.remove');
    Route::post('/im/messages/{msgId}/forward', [InternalMessagingController::class, 'forwardMessage'])->name('im.message.forward');
    Route::get('/im/conversations/{convId}/summary', [InternalMessagingController::class, 'summarize'])->name('im.summary');

    Route::get('/hub', fn () => redirect('/im'))->name('hub.index');
});
