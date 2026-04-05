<?php

namespace App\Console\Commands;

use App\Services\SecurityAnomalyService;
use Illuminate\Console\Command;

/**
 * K3 — Güvenlik anomali tespiti (saatlik).
 */
class SecurityAnomalyCheckCommand extends Command
{
    protected $signature   = 'security:anomaly-check {--dry-run : Sadece raporla, log yazma}';
    protected $description = 'Olağandışı erişim paternlerini tespit eder.';

    public function handle(SecurityAnomalyService $service): int
    {
        $anomalies = $service->detect();

        if (empty($anomalies)) {
            $this->info('Anomali tespit edilmedi.');
            return self::SUCCESS;
        }

        foreach ($anomalies as $a) {
            $icon = match ($a['severity'] ?? 'info') {
                'critical' => '🔴',
                'warning'  => '🟡',
                default    => '🔵',
            };
            $this->line("{$icon} [{$a['severity']}] {$a['type']}: {$a['detail']}");
        }

        $this->warn(count($anomalies) . ' anomali tespit edildi.');
        return self::SUCCESS;
    }
}
