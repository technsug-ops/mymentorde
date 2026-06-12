<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Services\Platform\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Platform Owner — Denetim kayitlari (audit log) viewer.
 *
 * /platform/audit-log altinda calisir. Cross-tenant tum platform event'lerini
 * goruntuler. Manager paneline gizli — sadece Platform Owner erisebilir.
 */
class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $svc)
    {
    }

    // ────────────────────────────────────────────────────────────────────────
    // INDEX
    // ────────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $filters = $this->extractFilters($request);

        $logs = $this->svc->search($filters)->paginate(50)->withQueryString();

        $kpis            = $this->svc->todayKpis();
        $distinctEvents  = $this->svc->distinctEvents();
        $distinctActors  = $this->svc->distinctActors();

        return view('platform.audit-log.index', [
            'logs'            => $logs,
            'filters'         => $filters,
            'kpis'            => $kpis,
            'distinctEvents'  => $distinctEvents,
            'distinctActors'  => $distinctActors,
            'severities'      => PlatformAuditLog::SEVERITIES,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SHOW
    // ────────────────────────────────────────────────────────────────────────

    public function show(int $id): View
    {
        /** @var PlatformAuditLog $log */
        $log = PlatformAuditLog::query()->with('actor')->findOrFail($id);

        $related = $this->svc->relatedFor($log);

        return view('platform.audit-log.show', [
            'log'     => $log,
            'related' => $related,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // EXPORT (CSV)
    // ────────────────────────────────────────────────────────────────────────

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->extractFilters($request);

        // Export'un kendisini de logla (kim ne export etti)
        PlatformAuditLog::record(
            'platform.audit.exported',
            ['target_type' => 'audit_export', 'filters' => $filters],
            PlatformAuditLog::SEVERITY_INFO
        );

        return $this->svc->exportCsv($filters);
    }

    // ────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ────────────────────────────────────────────────────────────────────────

    private function extractFilters(Request $request): array
    {
        return [
            'event'         => trim((string) $request->query('event', '')),
            'event_prefix'  => trim((string) $request->query('event_prefix', '')),
            'actor_user_id' => (int) $request->query('actor_user_id', 0) ?: null,
            'actor_email'   => trim((string) $request->query('actor_email', '')),
            'actor_role'    => trim((string) $request->query('actor_role', '')),
            'target_type'   => trim((string) $request->query('target_type', '')),
            'target_id'     => (int) $request->query('target_id', 0) ?: null,
            'severity'      => trim((string) $request->query('severity', '')),
            'from'          => trim((string) $request->query('from', '')),
            'to'            => trim((string) $request->query('to', '')),
            'q'             => trim((string) $request->query('q', '')),
        ];
    }
}
