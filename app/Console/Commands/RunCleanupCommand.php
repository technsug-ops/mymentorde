<?php

namespace App\Console\Commands;

use App\Jobs\CleanupExpiredDataJob;
use Illuminate\Console\Command;

class RunCleanupCommand extends Command
{
    protected $signature   = 'system:cleanup';
    protected $description = 'Süresi dolmuş veri temizliği (tokens, reads, failed jobs)';

    public function handle(): void
    {
        CleanupExpiredDataJob::dispatch();
        $this->info('Cleanup job kuyruğa alındı.');
    }
}
