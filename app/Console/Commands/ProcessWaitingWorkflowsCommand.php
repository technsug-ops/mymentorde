<?php

namespace App\Console\Commands;

use App\Services\WorkflowEngineService;
use Illuminate\Console\Command;

class ProcessWaitingWorkflowsCommand extends Command
{
    protected $signature   = 'workflow:process-waiting';
    protected $description = 'Bekleme süresi dolan enrollment\'ları işle';

    public function handle(WorkflowEngineService $engine): int
    {
        $count = $engine->processWaitingEnrollments();
        $this->info("İşlenen enrollment: {$count}");
        return 0;
    }
}
