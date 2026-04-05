<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationDispatch;
use App\Services\NotificationScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationDispatchController extends Controller
{
    public function __construct(private readonly NotificationScopeService $scopeService) {}

    public function index(Request $request)
    {
        $user    = Auth::user();
        $status  = trim((string) $request->query('status', ''));
        $channel = trim((string) $request->query('channel', ''));

        $query = NotificationDispatch::query();

        // Rol + sahiplik filtresi (güvenlik katmanı)
        if ($user) {
            $this->scopeService->applyScope($query, $user);
        }

        return $query
            ->when($status !== '',  fn ($q) => $q->where('status', $status))
            ->when($channel !== '', fn ($q) => $q->where('channel', $channel))
            ->latest()
            ->limit(300)
            ->get();
    }

    public function markRead(NotificationDispatch $notificationDispatch)
    {
        if (!(bool) $notificationDispatch->is_read) {
            $notificationDispatch->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        $user = Auth::user();

        $query = NotificationDispatch::query()
            ->where('is_read', false)
            ->where('channel', 'in_app');

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $updated = $query->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['updated' => $updated]);
    }

    public function markSent(NotificationDispatch $notificationDispatch)
    {
        $notificationDispatch->update([
            'status'      => 'sent',
            'sent_at'     => now(),
            'failed_at'   => null,
            'fail_reason' => null,
        ]);

        return response()->json($notificationDispatch->fresh());
    }

    public function markFailed(Request $request, NotificationDispatch $notificationDispatch)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $notificationDispatch->update([
            'status'      => 'failed',
            'failed_at'   => now(),
            'sent_at'     => null,
            'fail_reason' => trim((string) ($data['reason'] ?? 'manual-fail-mark')),
        ]);

        return response()->json($notificationDispatch->fresh());
    }

    public function dispatchNow(Request $request)
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);
        $limit = (int) ($data['limit'] ?? 100);

        $rows = NotificationDispatch::query()
            ->where('status', 'queued')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $sent   = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $hasRecipient = !empty($row->recipient_email) || !empty($row->recipient_phone);
            if (!$hasRecipient && $row->channel !== 'in_app') {
                $row->update([
                    'status'      => 'failed',
                    'failed_at'   => now(),
                    'fail_reason' => 'recipient_missing',
                    'sent_at'     => null,
                ]);
                $failed++;
                continue;
            }

            SendNotificationJob::dispatch($row->id);
            $sent++;
        }

        return response()->json([
            'processed'        => $rows->count(),
            'sent'             => $sent,
            'failed'           => $failed,
            'remaining_queued' => (int) NotificationDispatch::query()->where('status', 'queued')->count(),
        ]);
    }

    public function retryFailed(Request $request)
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);
        $limit = (int) ($data['limit'] ?? 100);

        $rows = NotificationDispatch::query()
            ->where('status', 'failed')
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get();

        foreach ($rows as $row) {
            $row->update([
                'status'      => 'queued',
                'queued_at'   => now(),
                'failed_at'   => null,
                'fail_reason' => null,
                'sent_at'     => null,
            ]);
        }

        return response()->json([
            'retried'      => $rows->count(),
            'queued_total' => (int) NotificationDispatch::query()->where('status', 'queued')->count(),
        ]);
    }
}
