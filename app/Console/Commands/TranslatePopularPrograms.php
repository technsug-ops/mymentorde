<?php

namespace App\Console\Commands;

use App\Models\Program;
use App\Services\ProgramCatalog\ProgramTranslationService;
use Illuminate\Console\Command;

/**
 * En "popüler" canonical programları toplu olarak EN→TR çevir.
 *
 * Popülerlik kriteri (ilk versiyonda — analytics yok):
 *  - quality_score yüksek (eksiksiz veri)
 *  - description dolu (çevrilecek metin var)
 *  - description_tr boş (henüz çevrilmemiş)
 *  - büyük şehirlerde (Berlin/München/Hamburg/Köln/Frankfurt) öncelik
 *
 * Çalıştırma:
 *   php artisan programs:translate-popular --limit=100
 *
 * Maliyet: ~$0.0004/program × 100 = ~$0.04 (Gemini Flash)
 * Süre: ~3-5 sn/program × 100 = ~5-10 dk
 */
class TranslatePopularPrograms extends Command
{
    protected $signature = 'programs:translate-popular
        {--limit=100 : Maksimum kaç program çevrilsin}
        {--throttle=2 : Çağrılar arası bekleme (saniye)}
        {--company-id=1 : Hangi company API key\'i kullanılsın}
        {--field= : Öncelikli alan filtresi (engineering, computer-science, business, all). engineering = Engineering + Computer Science (TR adaylar genelde mühendislik istiyor)}';

    protected $description = 'En popüler canonical programları toplu olarak Türkçeye çevirir';

    public function handle(ProgramTranslationService $translator): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $throttle = max(0, (int) $this->option('throttle'));
        $companyId = (int) $this->option('company-id');

        $bigCities = ['Berlin', 'Munich', 'München', 'Hamburg', 'Cologne', 'Köln', 'Frankfurt', 'Stuttgart', 'Düsseldorf'];
        $field = strtolower((string) $this->option('field'));

        // Kriterleri uygula — büyük şehirler + dolu description + henüz TR yok + quality high
        $query = Program::query()
            ->active()
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->whereNull('description_tr');

        // Alan filtresi — TR aday öğrencilerin ağırlıkla istediği alanlar öncelik
        if ($field === 'engineering' || $field === 'eng' || $field === 'muhendislik') {
            $query->where(function ($q) {
                $q->where('study_fields', 'LIKE', '%Engineering%')
                  ->orWhere('study_fields', 'LIKE', '%Computer Science%');
            });
            $this->info('Filtre: Engineering + Computer Science öncelikli.');
        } elseif ($field === 'computer-science' || $field === 'cs') {
            $query->where('study_fields', 'LIKE', '%Computer Science%');
            $this->info('Filtre: Computer Science only.');
        } elseif ($field === 'business' || $field === 'isletme') {
            $query->where('study_fields', 'LIKE', '%Business%');
            $this->info('Filtre: Business Management and Economics.');
        }

        $candidates = $query
            ->orderByRaw("CASE WHEN location IN ('" . implode("','", $bigCities) . "') THEN 0 ELSE 1 END")
            ->orderByDesc('quality_score')
            ->orderBy('course_name')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('Çevrilecek program bulunamadı (description dolu + TR boş kombinasyonu).');
            return self::SUCCESS;
        }

        $this->info("Toplam {$candidates->count()} program çevrilecek (throttle: {$throttle} sn).");
        $bar = $this->output->createProgressBar($candidates->count());
        $bar->start();

        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($candidates as $i => $program) {
            try {
                $result = $translator->translateProgram($program, force: false, companyId: $companyId);
                if ($result['translated_fields'] > 0) $success++;
                elseif ($result['error']) $failed++;
                else $skipped++;
            } catch (\Throwable $e) {
                $failed++;
                if ($failed < 3) $this->newLine() && $this->warn("  ⚠ Hata: " . substr($e->getMessage(), 0, 100));
            }

            $bar->advance();
            if ($throttle > 0 && $i + 1 < $candidates->count()) sleep($throttle);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Pre-translate tamamlandı:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Başarılı çeviri', $success],
                ['Hata',            $failed],
                ['Atlandı',         $skipped],
                ['Toplam',          $candidates->count()],
            ]
        );

        return self::SUCCESS;
    }
}
