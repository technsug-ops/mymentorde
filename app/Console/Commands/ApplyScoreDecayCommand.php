<?php

namespace App\Console\Commands;

use App\Services\LeadScoreService;
use Illuminate\Console\Command;

class ApplyScoreDecayCommand extends Command
{
    protected $signature   = 'lead:apply-score-decay {--dry-run : Değişiklikleri uygulamadan say}';
    protected $description = 'Hareketsiz lead\'lerin puanını bozunma kurallarına göre düşür';

    public function handle(LeadScoreService $service): int
    {
        $this->info('Lead score decay başlatılıyor...');

        if ($this->option('dry-run')) {
            $this->warn('[DRY-RUN] Değişiklikler uygulanmayacak.');
        }

        $count = $service->applyDecay();

        $this->info("Decay uygulandı: {$count} lead güncellendi.");
        return 0;
    }
}
