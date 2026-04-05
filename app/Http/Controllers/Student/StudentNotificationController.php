<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentPortalTrait;
use App\Models\NotificationDispatch;
use Illuminate\Http\Request;

class StudentNotificationController extends Controller
{
    use StudentPortalTrait;

    public function notifications(Request $request)
    {
        $base          = $this->baseData($request, 'notifications', 'Bildirimlerim', 'Sistem bildirimleri ve hatirlatmalar.');
        $studentId     = (string) ($base['studentId'] ?? '');
        $filterChannel = trim((string) $request->query('channel', ''));
        $filterStatus  = trim((string) $request->query('status', ''));

        $notifications = collect();
        if ($studentId !== '') {
            $notifications = NotificationDispatch::query()
                ->where('student_id', $studentId)
                ->when($filterChannel !== '', fn ($q) => $q->where('channel', $filterChannel))
                ->when($filterStatus  !== '', fn ($q) => $q->where('status', $filterStatus))
                ->orderByDesc('id')
                ->limit(200)
                ->get(['id', 'channel', 'category', 'subject', 'body', 'status', 'is_read', 'read_at', 'sent_at', 'queued_at', 'failed_at']);
        }

        $unreadCount = $notifications->where('is_read', false)->where('status', 'sent')->count();

        return view('student.notifications', array_merge($base, [
            'notifications' => $notifications,
            'filterChannel' => $filterChannel,
            'filterStatus'  => $filterStatus,
            'unreadCount'   => $unreadCount,
        ]));
    }

    public function notificationMarkRead(Request $request, NotificationDispatch $notification)
    {
        $studentId = (string) (auth()->user()?->student_id ?? '');
        if ($notification->student_id === $studentId) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }

        return back();
    }

    public function notificationsReadAll(Request $request)
    {
        $base      = $this->baseData($request, 'notifications', '', '');
        $studentId = (string) ($base['studentId'] ?? '');
        if ($studentId !== '') {
            NotificationDispatch::where('student_id', $studentId)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        return back()->with('notif_success', 'Tüm bildirimler okundu işaretlendi.');
    }

}
