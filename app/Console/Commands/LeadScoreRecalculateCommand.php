<?php

namespace App\Console\Commands;

use App\Services\LeadScoreService;
use Illuminate\Console\Command;

class LeadScoreRecalculateCommand extends Command
{
    protected $signature = 'leads:recalculate-scores
                            {--limit=500 : İşlenecek maksimum kayıt sayısı}
                            {--all : Limit olmaksızın tüm kayıtları işle}';

    protected $description = 'Açık GuestApplication kayıtlarının lead_score ve lead_score_tier alanlarını yeniden hesapla';

    public function handle(LeadScoreService $service): int
    {
        $limit = $this->option('all') ? null : (int) $this->option('limit');

        $this->info('Lead score yeniden hesaplama başladı' . ($limit ? " (limit: {$limit})" : ' (tümü)') . '...');

        $count = $service->recalculateAll($limit);

        $this->info("{$count} GuestApplication kaydı güncellendi.");

        return self::SUCCESS;
    }
}
