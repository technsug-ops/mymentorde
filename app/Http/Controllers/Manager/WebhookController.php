<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request)
    {
        $cid    = (int) ($request->user()?->company_id ?? 0);
        $source = $request->query('source', '');
        $status = $request->query('status', '');

        $logs = WebhookLog::when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->when($source, fn($q) => $q->where('source', $source))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(50);

        $sources = WebhookLog::when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->distinct()->pluck('source');

        $stats = [
            'total'  => WebhookLog::when($cid > 0, fn($q) => $q->where('company_id', $cid))->count(),
            'failed' => WebhookLog::when($cid > 0, fn($q) => $q->where('company_id', $cid))->where('status', 'failed')->count(),
            'today'  => WebhookLog::when($cid > 0, fn($q) => $q->where('company_id', $cid))->whereDate('created_at', today())->count(),
        ];

        return view('manager.webhooks.index', compact('logs', 'sources', 'stats'));
    }

    public function retry(Request $request, WebhookLog $log): \Illuminate\Http\RedirectResponse
    {
        $log->update([
            'status'        => 'received',
            'retry_count'   => $log->retry_count + 1,
            'error_message' => null,
        ]);
        return back()->with('status', "Webhook #{$log->id} yeniden kuyruğa alındı.");
    }

    public function destroy(WebhookLog $log): \Illuminate\Http\RedirectResponse
    {
        $log->delete();
        return back()->with('status', 'Log silindi.');
    }

    // Gelen webhook'ları kaydet (harici sistemlerden POST)
    public function receive(Request $request, string $source): \Illuminate\Http\JsonResponse
    {
        WebhookLog::create([
            'company_id'   => null,
            'source'       => substr($source, 0, 64),
            'event_type'   => $request->header('X-Event-Type') ?? $request->input('event') ?? 'unknown',
            'status'       => 'received',
            'payload'      => $request->all(),
            'processed_at' => now(),
        ]);
        return response()->json(['ok' => true]);
    }
}
