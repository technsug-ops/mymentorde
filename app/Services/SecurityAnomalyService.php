<?php

namespace App\Services;

use App\Models\AccountAccessLog;
use App\Models\SystemEventLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * K3 — Güvenlik Anomali Tespiti
 */
class SecurityAnomalyService
{
    public function detect(): array
    {
        $anomalies = collect();

        // 1. Aynı kullanıcı son 1 saatte 3+ farklı IP'den aktif oturum
        try {
            $multiIp = DB::table('sessions')
                ->selectRaw('user_id, COUNT(DISTINCT ip_address) as ip_count')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subHour()->timestamp)
                ->groupBy('user_id')
                ->having('ip_count', '>', 2)
                ->get();

            foreach ($multiIp as $row) {
                $anomalies->push([
                    'type'     => 'multi_ip_login',
                    'severity' => 'warning',
                    'user_id'  => $row->user_id,
                    'detail'   => "{$row->ip_count} farklı IP'den aktif oturum.",
                ]);
            }
        } catch (\Throwable) {}

        // 2. Gece saatlerinde (00:00-05:00) hassas veri erişimi
        try {
            $nightAccess = SystemEventLog::where('created_at', '>=', now()->subDay())
                ->whereRaw(
                    config('database.default') === 'sqlite'
                        ? "CAST(strftime('%H', created_at) AS INTEGER) BETWEEN 0 AND 5"
                        : 'HOUR(created_at) BETWEEN 0 AND 5'
                )
                ->whereIn('event_type', ['gdpr.pii_access', 'vault.revealed'])
                ->count();

            if ($nightAccess > 0) {
                $anomalies->push([
                    'type'     => 'night_access',
                    'severity' => 'info',
                    'detail'   => "{$nightAccess} hassas veri erişimi gece saatlerinde gerçekleşti.",
                ]);
            }
        } catch (\Throwable) {}

        // 3. Kısa sürede çok fazla vault reveal (1 saatte 20+)
        try {
            $massReveal = AccountAccessLog::where('created_at', '>=', now()->subHour())
                ->selectRaw('user_id, COUNT(*) as cnt')
                ->groupBy('user_id')
                ->having('cnt', '>', 20)
                ->get();

            foreach ($massReveal as $row) {
                $anomalies->push([
                    'type'     => 'mass_vault_reveal',
                    'severity' => 'critical',
                    'user_id'  => $row->user_id,
                    'detail'   => "1 saatte {$row->cnt} vault şifre görüntüleme.",
                ]);
            }
        } catch (\Throwable) {}

        // 4. Kritik bildirim job'larında 2+ başarısız girişim (son 1 saat)
        try {
            if (Schema::hasTable('failed_jobs')) {
                $failedCount = DB::table('failed_jobs')
                    ->where('failed_at', '>=', now()->subHour())
                    ->count();

                if ($failedCount >= 2) {
                    // Hangi job class'larının başarısız olduğunu grupla
                    $failedGroups = DB::table('failed_jobs')
                        ->where('failed_at', '>=', now()->subHour())
                        ->selectRaw('payload, COUNT(*) as cnt')
                        ->groupBy('payload')
                        ->limit(5)
                        ->get()
                        ->map(function ($row): string {
                            try {
                                $payload = json_decode($row->payload, true);
                                return ($payload['displayName'] ?? 'UnknownJob') . " (×{$row->cnt})";
                            } catch (\Throwable) {
                                return "UnknownJob (×{$row->cnt})";
                            }
                        })
                        ->implode(', ');

                    $anomalies->push([
                        'type'     => 'failed_jobs_spike',
                        'severity' => $failedCount >= 5 ? 'critical' : 'warning',
                        'detail'   => "Son 1 saatte {$failedCount} başarısız job: {$failedGroups}",
                    ]);
                }
            }
        } catch (\Throwable) {}

        return $anomalies->all();
    }
}
