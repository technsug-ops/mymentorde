<?php

namespace App\Console\Commands;

use App\Services\EscalationService;
use Illuminate\Console\Command;

class ProcessEscalationsCommand extends Command
{
    protected $signature = 'escalations:process {--limit=100 : Max rules to process}';
    protected $description = 'Process escalation rules and queue notifications';

    public function handle(EscalationService $service): int
    {
        $limit  = max(1, (int) $this->option('limit'));
        $result = $service->process($limit);
        $this->info(
            "escalations:process tamamlandi | rules: {$result['rules']} | events: {$result['events_created']} | queued: {$result['notifications_queued']}"
        );
        return 0;
    }
}
