<?php

namespace App\Console\Commands;

use App\Services\RiskScoreService;
use Illuminate\Console\Command;

class CalculateRiskScoresCommand extends Command
{
    protected $signature = 'risk-scores:calculate {--limit=200 : Max students to process}';
    protected $description = 'Calculate student risk scores';

    public function handle(RiskScoreService $service): int
    {
        $limit  = max(1, (int) $this->option('limit'));
        $result = $service->calculate($limit);
        $this->info("risk-scores:calculate tamamlandi | processed: {$result['processed']} | updated: {$result['updated']}");
        return 0;
    }
}
