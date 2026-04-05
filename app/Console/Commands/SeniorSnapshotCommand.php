<?php

namespace App\Console\Commands;

use App\Services\SeniorPerformanceService;
use Illuminate\Console\Command;

class SeniorSnapshotCommand extends Command
{
    protected $signature = 'senior:snapshot-performance
                            {--period= : YYYY-MM formatında dönem, default mevcut ay}
                            {--dry-run : Kaydetmeden kaç senior işleneceğini göster}';

    protected $description = 'Senior aylık performans snapshotlarını oluştur (senior_performance_snapshots)';

    public function handle(SeniorPerformanceService $service): int
    {
        $period = (string) ($this->option('period') ?: now()->format('Y-m'));

        if ($this->option('dry-run')) {
            $this->info("Dry-run: {$period} dönemi için snapshot çalışacak.");
            return self::SUCCESS;
        }

        $this->info("Senior performans snapshot başlatıldı: {$period}");
        $count = $service->snapshotMonth($period);
        $this->info("{$count} senior için snapshot kaydedildi/güncellendi.");

        return self::SUCCESS;
    }
}
