<?php

namespace App\Console\Commands;

use App\Services\TaskRecurringService;
use Illuminate\Console\Command;

class CloneRecurringTasksCommand extends Command
{
    protected $signature = 'tasks:clone-recurring {--dry-run : Klonlama yapmadan rapor et}';
    protected $description = 'Tekrarlayan task\'ları klonlar ve next_run_at günceller (her gün 06:00)';

    public function handle(TaskRecurringService $service): int
    {
        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] Recurring task klonlama simüle ediliyor...');
            return 0;
        }

        $cloned = $service->processRecurring();
        $this->info("tasks:clone-recurring | cloned={$cloned}");

        return 0;
    }
}
