<?php

namespace App\Console\Commands;

use App\Models\GuestApplication;
use App\Models\MarketingTask;
use Illuminate\Console\Command;

class LeadReengagementCheckCommand extends Command
{
    protected $signature   = 'lead:re-engagement-check {--dry-run}';
    protected $description = '90 gün hareketsiz lead\'leri tespit et ve re-engagement task\'ı oluştur';

    public function handle(): int
    {
        $cutoff = now()->subDays(90);

        $pool = GuestApplication::withoutGlobalScope('company')
            ->where('converted_to_student', false)
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->where('updated_at', '<', $cutoff)
            ->where('contract_status', '!=', 'approved')
            ->get();

        $this->info("Re-engagement havuzu: {$pool->count()} lead");

        if ($this->option('dry-run')) {
            $this->warn('[DRY-RUN] Task oluşturulmayacak.');
            return 0;
        }

        $taskCreated = 0;
        foreach ($pool as $guest) {
            // Create task only if not already has a pending re-engagement task
            $exists = MarketingTask::where('source_ref', "re_engagement:guest:{$guest->id}")
                ->where('status', 'pending')
                ->exists();

            if (! $exists) {
                MarketingTask::create([
                    'title'      => "Re-Engagement: {$guest->first_name} {$guest->last_name}",
                    'department' => 'sales',
                    'priority'   => 'medium',
                    'status'     => 'pending',
                    'source'     => 're_engagement',
                    'source_ref' => "re_engagement:guest:{$guest->id}",
                ]);
                $taskCreated++;
            }
        }

        $this->info("Oluşturulan task: {$taskCreated}");
        return 0;
    }
}
