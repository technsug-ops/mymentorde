<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\NotificationDispatch;
use App\Services\NotificationScopeService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationScopeService $scopeService) {}

    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', 'all'));
        if (!in_array($status, ['all', 'queued', 'sent', 'failed', 'skipped'], true)) {
            $status = 'all';
        }

        $channel = trim((string) $request->query('channel', 'all'));
        if (!in_array($channel, ['all', 'email', 'whatsapp', 'in_app'], true)) {
            $channel = 'all';
        }

        $studentId = trim((string) $request->query('student_id', ''));

        $query = NotificationDispatch::query();

        // Rol + sahiplik filtresi
        $this->scopeService->applyScope($query, $request->user());

        $rows = $query
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($channel !== 'all', fn ($q) => $q->where('channel', $channel))
            ->when($studentId !== '', fn ($q) => $q->where('student_id', $studentId))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $companyId = $request->user()?->company_id;
        $statsBase = NotificationDispatch::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $stats = [
            'queued'   => (int) (clone $statsBase)->where('status', 'queued')->count(),
            'failed'   => (int) (clone $statsBase)->where('status', 'failed')->count(),
            'sent_24h' => (int) (clone $statsBase)->where('status', 'sent')
                ->where('sent_at', '>=', now()->subDay())->count(),
        ];

        return view('marketing-admin.notifications.index', [
            'pageTitle' => 'Bildirimler',
            'title' => 'Bildirim Listesi',
            'rows' => $rows,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'channel' => $channel,
                'student_id' => $studentId,
            ],
        ]);
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

        $sent = 0;
        $failed = 0;
        foreach ($rows as $row) {
            $hasRecipient = !empty($row->recipient_email) || !empty($row->recipient_phone);
            if (!$hasRecipient) {
                $row->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'fail_reason' => 'recipient missing',
                    'sent_at' => null,
                ]);
                $failed++;
                continue;
            }

            $row->update([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'fail_reason' => null,
            ]);
            $sent++;
        }

        return redirect('/mktg-admin/notifications')
            ->with('status', "Dispatch tamamlandi | processed: {$rows->count()} | sent: {$sent} | failed: {$failed}");
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
                'status' => 'queued',
                'queued_at' => now(),
                'failed_at' => null,
                'fail_reason' => null,
                'sent_at' => null,
            ]);
        }

        return redirect('/mktg-admin/notifications')
            ->with('status', "Retry tamamlandi | queued: {$rows->count()}");
    }

    public function markSent(string $id)
    {
        $row = NotificationDispatch::query()->findOrFail($id);
        $row->update([
            'status' => 'sent',
            'sent_at' => now(),
            'failed_at' => null,
            'fail_reason' => null,
        ]);

        return redirect('/mktg-admin/notifications')->with('status', "Kayit guncellendi (#{$row->id}) -> sent");
    }

    public function markFailed(Request $request, string $id)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $row = NotificationDispatch::query()->findOrFail($id);
        $row->update([
            'status' => 'failed',
            'failed_at' => now(),
            'sent_at' => null,
            'fail_reason' => trim((string) ($data['reason'] ?? 'manual-fail-mark')),
        ]);

        return redirect('/mktg-admin/notifications')->with('status', "Kayit guncellendi (#{$row->id}) -> failed");
    }

    public function markRead(string $id)
    {
        $row = NotificationDispatch::query()->findOrFail($id);
        $row->update([
            'status' => 'sent',
            'sent_at' => now(),
            'failed_at' => null,
            'fail_reason' => null,
        ]);

        return response()->json(['ok' => true, 'id' => $id, 'read' => true]);
    }
}
