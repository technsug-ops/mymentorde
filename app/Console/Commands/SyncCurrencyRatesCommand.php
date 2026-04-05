<?php

namespace App\Console\Commands;

use App\Services\CurrencyRateService;
use Illuminate\Console\Command;

class SyncCurrencyRatesCommand extends Command
{
    protected $signature   = 'currency:sync-rates';
    protected $description = 'Fetch latest EUR exchange rates from open.er-api.com and store in DB';

    public function handle(CurrencyRateService $service): int
    {
        $this->info('Fetching EUR exchange rates...');

        $synced = $service->sync();

        if (empty($synced)) {
            $this->error('Failed to sync rates. Check logs for details.');
            return self::FAILURE;
        }

        foreach ($synced as $currency => $rate) {
            $this->line("  EUR/{$currency} = {$rate}");
        }

        $this->info('Rates synced successfully.');
        return self::SUCCESS;
    }
}
