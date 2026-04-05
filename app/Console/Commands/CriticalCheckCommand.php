<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CriticalCheckCommand extends Command
{
    protected $signature = 'ops:critical-check {--limit=100 : Passed to sub-commands}';
    protected $description = 'Run all critical runtime checks in sequence';

    public function handle(): int
    {
        $this->line('ops:critical-check basladi...');

        $limit    = max(1, (int) $this->option('limit'));
        $failures = [];

        $run = function (string $label, string $command) use (&$failures): void {
            $exit   = Artisan::call($command);
            $output = trim((string) Artisan::output());
            $this->line("[{$label}] exit={$exit}");
            if ($output !== '') { $this->line($output); }
            if ($exit !== 0) { $failures[] = $label; }
        };

        $run('mvp_smoke',     'mvp:smoke --cleanup');
        $run('api_regression', 'api:regression-smoke');
        $run('self_heal',      "ops:self-heal --limit={$limit}");

        if (!empty($failures)) {
            $this->error('ops:critical-check SONUC: FAIL | failed_steps: ' . implode(', ', $failures));
            return 1;
        }

        $this->info('ops:critical-check SONUC: PASS');
        return 0;
    }
}
