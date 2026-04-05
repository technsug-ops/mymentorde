<?php

namespace App\Console\Commands;

use App\Models\AutomationEnrollment;
use Illuminate\Console\Command;

class CheckWorkflowGoalsCommand extends Command
{
    protected $signature   = 'workflow:check-goals {--dry-run : Sadece sayı göster, kayıt yapma}';
    protected $description = 'Goal node olan enrollment\'ları kontrol et, hedefe ulaşanları tamamla';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Active enrollment'lardan goal node'da bekleyenleri bul
        $enrollments = AutomationEnrollment::query()
            ->where('status', 'waiting')
            ->whereHas('currentNode', fn ($q) => $q->where('node_type', 'goal_check'))
            ->with(['workflow', 'guestApplication', 'currentNode'])
            ->limit(500)
            ->get();

        $completed = 0;
        $checked   = 0;

        foreach ($enrollments as $enrollment) {
            $checked++;
            $nodeConfig = (array) ($enrollment->currentNode?->node_config ?? []);
            $goal       = (string) ($nodeConfig['goal'] ?? '');
            $guest      = $enrollment->guestApplication;

            if (! $guest) {
                continue;
            }

            $goalReached = match ($goal) {
                'contract_requested' => in_array((string) ($guest->contract_status ?? ''), ['requested', 'signed_uploaded', 'approved'], true),
                'contract_approved'  => (string) ($guest->contract_status ?? '') === 'approved',
                'form_completed'     => (bool) ($guest->registration_completed_at ?? false),
                'package_selected'   => (bool) ($guest->package_selected_at ?? false),
                'document_uploaded'  => $guest->documents()->exists(),
                default              => false,
            };

            if ($goalReached) {
                $completed++;
                if (! $dryRun) {
                    $enrollment->update([
                        'status'       => 'completed',
                        'completed_at' => now(),
                        'exit_reason'  => 'goal_reached',
                    ]);
                }
            }
        }

        $mode = $dryRun ? '[DRY-RUN] ' : '';
        $this->info("{$mode}Kontrol edildi: {$checked} | Hedef ulaşıldı: {$completed}");

        return Command::SUCCESS;
    }
}
