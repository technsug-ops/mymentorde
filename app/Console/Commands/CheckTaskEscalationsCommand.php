<?php

namespace App\Console\Commands;

use App\Services\TaskEscalationService;
use Illuminate\Console\Command;

class CheckTaskEscalationsCommand extends Command
{
    protected $signature = 'tasks:check-escalations {--dry-run : Aksiyon almadan rapor et}';
    protected $description = 'SLA aşan task\'ları escalate eder (her 30 dakikada bir çalışır)';

    public function handle(TaskEscalationService $service): int
    {
        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] Escalation kontrolü simüle ediliyor...');
            return 0;
        }

        $count = $service->checkAndEscalate();
        $this->info("task:check-escalations | escalated={$count}");

        return 0;
    }
}
