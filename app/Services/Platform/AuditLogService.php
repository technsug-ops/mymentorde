<?php

namespace App\Services\Platform;

use App\Models\PlatformAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Platform Audit Log Service.
 *
 * Iki ana sorumluluk:
 *  1) Yeni audit event yazmak (controller'lardan kisa yol)
 *  2) Filter/search + CSV export (UI icin)
 *
 * Mevcut audit_trails tablosunu bozmaz — PlatformAuditLog onun yaninda
 * platform owner seviyesinde event'leri taklit eder.
 */
class AuditLogService
{
    /**
     * Tek satirla event yaz.
     *
     * @param  string  $event    'platform.impersonate.start' gibi
     * @param  array   $context  ['target_type'=>'company','target_id'=>5,'data'=>[...]]
     * @param  string  $severity info|warning|critical
     */
    public function log(string $event, array $context = [], string $severity = PlatformAuditLog::SEVERITY_INFO): ?PlatformAuditLog
    {
        return PlatformAuditLog::record($event, $context, $severity);
    }

    /**
     * Filtreli query — UI listesi ve CSV export'unun ortak iskelesi.
     *
     * Desteklenen filtreler:
     *   - event           string (exact)
     *   - event_prefix    string ('platform.billing')
     *   - actor_user_id   int
     *   - actor_email     string (like)
     *   - actor_role      string
     *   - target_type     string
     *   - target_id       int
     *   - severity        info|warning|critical
     *   - from            Y-m-d
     *   - to              Y-m-d
     *   - q               string (event/actor_email/target_type icinde free-text)
     */
    public function search(array $filters): Builder
    {
        $q = PlatformAuditLog::query()->with('actor');

        if (!empty($filters['event'])) {
            $q->where('event', $filters['event']);
        }
        if (!empty($filters['event_prefix'])) {
            $q->where('event', 'like', $filters['event_prefix'] . '%');
        }
        if (!empty($filters['actor_user_id'])) {
            $q->where('actor_user_id', (int) $filters['actor_user_id']);
        }
        if (!empty($filters['actor_email'])) {
            $q->where('actor_email', 'like', '%' . $filters['actor_email'] . '%');
        }
        if (!empty($filters['actor_role'])) {
            $q->where('actor_role', $filters['actor_role']);
        }
        if (!empty($filters['target_type'])) {
            $q->where('target_type', $filters['target_type']);
        }
        if (!empty($filters['target_id'])) {
            $q->where('target_id', (int) $filters['target_id']);
        }
        if (!empty($filters['severity']) && in_array($filters['severity'], PlatformAuditLog::SEVERITIES, true)) {
            $q->where('severity', $filters['severity']);
        }
        if (!empty($filters['from'])) {
            $q->whereDate('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $q->whereDate('created_at', '<=', $filters['to']);
        }
        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->where(function ($qq) use ($term): void {
                $qq->where('event', 'like', $term)
                    ->orWhere('actor_email', 'like', $term)
                    ->orWhere('target_type', 'like', $term);
            });
        }

        return $q->orderByDesc('id');
    }

    /**
     * KPI'lar — bugune ait ozet (4 tile icin)
     *
     * @return array{total:int,critical:int,unique_actors:int,top_event:?string,top_event_count:int}
     */
    public function todayKpis(): array
    {
        if (!Schema::hasTable('platform_audit_logs')) {
            return [
                'total'           => 0,
                'critical'        => 0,
                'unique_actors'   => 0,
                'top_event'       => null,
                'top_event_count' => 0,
            ];
        }

        $start = now()->startOfDay();

        $base = PlatformAuditLog::query()->where('created_at', '>=', $start);

        $total    = (clone $base)->count();
        $critical = (clone $base)->where('severity', PlatformAuditLog::SEVERITY_CRITICAL)->count();
        $uniqueActors = (clone $base)
            ->whereNotNull('actor_user_id')
            ->distinct('actor_user_id')
            ->count('actor_user_id');

        $top = (clone $base)
            ->selectRaw('event, COUNT(*) as cnt')
            ->groupBy('event')
            ->orderByDesc('cnt')
            ->first();

        return [
            'total'           => (int) $total,
            'critical'        => (int) $critical,
            'unique_actors'   => (int) $uniqueActors,
            'top_event'       => $top?->event,
            'top_event_count' => (int) ($top?->cnt ?? 0),
        ];
    }

    /**
     * Distinct event listesi (filter dropdown icin) — son 90 gunden.
     */
    public function distinctEvents(int $days = 90): array
    {
        if (!Schema::hasTable('platform_audit_logs')) {
            return [];
        }
        return PlatformAuditLog::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->all();
    }

    /**
     * Aktif platform owner + aktif manager actor'lari (filter icin).
     */
    public function distinctActors(int $limit = 50): array
    {
        if (!Schema::hasTable('platform_audit_logs')) {
            return [];
        }
        $rows = PlatformAuditLog::query()
            ->whereNotNull('actor_user_id')
            ->select('actor_user_id', 'actor_email')
            ->groupBy('actor_user_id', 'actor_email')
            ->orderBy('actor_email')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->actor_user_id] = $r->actor_email ?: ('user#' . $r->actor_user_id);
        }
        return $out;
    }

    /**
     * CSV export — filtre uygulanmis kayitlar (streaming, memory-safe).
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $filename = 'platform-audit-' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($filters): void {
            $out = fopen('php://output', 'w');
            // BOM (Excel UTF-8)
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'id', 'created_at', 'severity', 'event',
                'actor_user_id', 'actor_email', 'actor_role', 'actor_ip',
                'target_type', 'target_id', 'context_json',
            ]);

            $this->search($filters)
                ->orderBy('id')
                ->chunk(500, function ($rows) use ($out): void {
                    foreach ($rows as $r) {
                        /** @var PlatformAuditLog $r */
                        fputcsv($out, [
                            $r->id,
                            optional($r->created_at)->toIso8601String(),
                            $r->severity,
                            $r->event,
                            $r->actor_user_id,
                            $r->actor_email,
                            $r->actor_role,
                            $r->actor_ip,
                            $r->target_type,
                            $r->target_id,
                            $r->context ? json_encode($r->context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Aktor + 5 dakikalik pencere icinde olusmus diger event'ler.
     * Detay sayfasinda 'related events' bloku icin.
     */
    public function relatedFor(PlatformAuditLog $log, int $minutes = 5, int $limit = 20): array
    {
        if (!$log->actor_user_id) {
            return [];
        }
        return PlatformAuditLog::query()
            ->where('id', '!=', $log->id)
            ->where('actor_user_id', $log->actor_user_id)
            ->whereBetween('created_at', [
                $log->created_at?->copy()->subMinutes($minutes),
                $log->created_at?->copy()->addMinutes($minutes),
            ])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->all();
    }
}
