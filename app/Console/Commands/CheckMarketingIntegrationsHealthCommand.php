<?php

namespace App\Console\Commands;

use App\Services\Marketing\IntegrationHealthService;
use Illuminate\Console\Command;

class CheckMarketingIntegrationsHealthCommand extends Command
{
    protected $signature = 'marketing:integrations-health {--limit=200 : Max connections to check}';
    protected $description = 'Evaluate marketing integration connection health (token expiry and status)';

    public function handle(IntegrationHealthService $service): int
    {
        $limit  = max(1, (int) $this->option('limit'));
        $result = $service->run($limit);

        $this->info(sprintf(
            'marketing:integrations-health tamamlandi | checked:%d | updated:%d | expiring:%d | expired:%d | errors:%d',
            (int) ($result['checked'] ?? 0),
            (int) ($result['updated'] ?? 0),
            (int) ($result['expiring'] ?? 0),
            (int) ($result['expired'] ?? 0),
            (int) ($result['errors'] ?? 0)
        ));

        return 0;
    }
}
