<?php

namespace App\Console\Commands;

use App\Services\Marketing\ExternalMetrics\ExternalMetricsSyncService;
use Illuminate\Console\Command;

class SyncExternalMetricsCommand extends Command
{
    protected $signature = 'marketing:sync-external-metrics
                            {--provider=* : Provider filter (meta, google_ads, ga4)}
                            {--days= : Number of days to sync}
                            {--dry-run : Simulate without saving}';

    protected $description = 'Sync Meta / GA4 / Google Ads metrics into local marketing_external_metrics table';

    public function handle(ExternalMetricsSyncService $service): int
    {
        $providers = (array) $this->option('provider');
        $daysOpt   = trim((string) ($this->option('days') ?? ''));
        $days      = $daysOpt !== '' ? (int) $daysOpt : null;
        $dryRun    = (bool) $this->option('dry-run');

        $result = $service->sync($providers, $days, $dryRun);

        $window = $result['window'] ?? [];
        $this->line(sprintf(
            'window: %s -> %s | days: %s | dry-run: %s',
            (string) ($window['start_date'] ?? '-'),
            (string) ($window['end_date'] ?? '-'),
            (string) ($window['days'] ?? '-'),
            $dryRun ? 'yes' : 'no'
        ));

        foreach ($result['providers'] ?? [] as $provider => $meta) {
            $this->line(sprintf(
                '%s => %s | rows:%d%s',
                (string) $provider,
                (string) ($meta['status'] ?? '-'),
                (int) ($meta['rows'] ?? 0),
                !empty($meta['message']) ? ' | ' . $meta['message'] : ''
            ));
        }

        $this->info(sprintf(
            'marketing:sync-external-metrics tamamlandi | fetched:%d | saved:%d',
            (int) ($result['fetched_rows'] ?? 0),
            (int) ($result['saved_rows'] ?? 0)
        ));

        return 0;
    }
}
