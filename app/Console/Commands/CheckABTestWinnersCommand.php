<?php

namespace App\Console\Commands;

use App\Services\ABTestingService;
use Illuminate\Console\Command;

class CheckABTestWinnersCommand extends Command
{
    protected $signature   = 'abtest:check-winners';
    protected $description = 'A/B testlerde istatistiksel anlamlılığı kontrol et, auto_winner=true ise kazananı uygula';

    public function handle(ABTestingService $service): int
    {
        $applied = $service->checkAndAutoApplyWinners();
        $this->info("Kazanan uygulanan test: {$applied}");
        return 0;
    }
}
