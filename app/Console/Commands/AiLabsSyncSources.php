<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\AiLabs\KnowledgeBaseService;
use Illuminate\Console\Command;

/**
 * AI Labs bilgi havuzu kaynaklarını Gemini File API'ye senkronize eder.
 *
 * Kullanım:
 *   php artisan ai-labs:sync-sources                 # tüm şirketler
 *   php artisan ai-labs:sync-sources --company=1     # belirli şirket
 */
class AiLabsSyncSources extends Command
{
    protected $signature = 'ai-labs:sync-sources {--company= : Belirli bir company_id için senkronize et}';

    protected $description = 'AI Labs PDF kaynaklarını Gemini File API\'ye yükle (sync)';

    public function handle(KnowledgeBaseService $kb): int
    {
        $companyId = $this->option('company') ? (int) $this->option('company') : null;

        $companies = $companyId
            ? Company::query()->where('id', $companyId)->get()
            : Company::query()->where('is_active', true)->get();

        if ($companies->isEmpty()) {
            $this->error('Hiç aktif şirket bulunamadı.');
            return self::FAILURE;
        }

        $totalSynced = 0;
        $totalFailed = 0;

        foreach ($companies as $company) {
            $this->line("— Şirket #{$company->id}: {$company->name}");
            $result = $kb->syncAllSources((int) $company->id);

            $this->line("  Senkronize: <fg=green>{$result['synced']}</>, Atlandı: {$result['skipped']}, Başarısız: <fg=red>{$result['failed']}</>");

            foreach ($result['errors'] as $err) {
                $this->line("    ✗ {$err}", 'error');
            }

            $totalSynced += $result['synced'];
            $totalFailed += $result['failed'];
        }

        $this->newLine();
        $this->info("Toplam: {$totalSynced} senkronize, {$totalFailed} başarısız.");

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
