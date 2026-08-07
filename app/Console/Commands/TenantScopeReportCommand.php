<?php

namespace App\Console\Commands;

use App\Support\TenantScopeReport;
use Illuminate\Console\Command;

/**
 * Tenant dönüşümü hazırlık raporu — konsol yüzü.
 *
 * Ölçümün kendisi App\Support\TenantScopeReport'ta; aynı sayıları
 * `/platform/tenant-scope` ekranı da gösteriyor. Canlıda SSH olmadığı için
 * asıl ölçüm panelden alınıyor, bu komut yerel/CI içindir.
 *
 *     php artisan tenant:scope-report
 *
 * Çıkış kodu: sahibi bilinmeyen satır varsa 1 — dağıtım betiğinde kapı
 * olarak kullanılabilir.
 */
class TenantScopeReportCommand extends Command
{
    protected $signature = 'tenant:scope-report {--table=* : Yalnızca bu tabloları raporla}';

    protected $description = 'company_id boş kalmış satırları tablo tablo raporlar (tenant dönüşümü öncesi kontrol)';

    public function handle(TenantScopeReport $report): int
    {
        $only = (array) $this->option('table');

        $result = $report->run($only !== [] ? $only : null);

        $this->table(
            ['Tablo', 'Satır', 'Sahipsiz', 'Fabrika', 'Durum'],
            array_map(static fn (array $row): array => [
                $row['table'],
                number_format($row['total'], 0, ',', '.'),
                $row['unowned'] > 0 ? number_format($row['unowned'], 0, ',', '.') : '—',
                $row['factory'] > 0 ? number_format($row['factory'], 0, ',', '.') : '—',
                $row['status'],
            ], $result['rows'])
        );

        if ($result['skipped'] !== []) {
            $this->line('Atlandı (tablo ya da kolon yok): ' . implode(', ', $result['skipped']));
        }

        if ($result['factory'] > 0) {
            $this->newLine();
            $this->line(sprintf(
                '%s fabrika satırı (company_id = 0) — beyan edilmiş, miras yolundan okunuyor.',
                number_format($result['factory'], 0, ',', '.')
            ));
        }

        if ($result['unowned'] === 0) {
            $this->newLine();
            $this->info('Sahibi bilinmeyen satır yok — bu tablolara BelongsToCompany eklenebilir.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error(sprintf(
            '%s satırın sahibi bilinmiyor. Trait EKLEMEYİN — eklenirse bu kayıtlar ekranlardan kaybolur.',
            number_format($result['unowned'], 0, ',', '.')
        ));
        $this->line('Önce: php artisan migrate --force   (geri-doldurma migration\'ları)');
        $this->line('Sonra bu raporu tekrar alın.');

        return self::FAILURE;
    }
}
