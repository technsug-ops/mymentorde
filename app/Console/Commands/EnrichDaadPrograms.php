<?php

namespace App\Console\Commands;

use App\Models\Program;
use App\Models\ProgramSourceLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DAAD detail page'lerinden description, requirements, application info çekip
 * canonical Program tablosunu zenginleştirir.
 *
 * Solr search.json sadece liste verir (course name, üni, dil, ücret) ama
 * description boş kalır. Bu yüzden DAAD programları wizard sıralamasında
 * Expatrio'ya yenilir. Bu komut detail HTML'den description çeker ve
 * quality_score'u artırır.
 *
 * URL pattern: https://www2.daad.de/deutschland/studienangebote/international-programmes/{lang}/detail/{id}/
 *
 * Çalıştırma:
 *   php artisan programs:enrich-daad
 *   php artisan programs:enrich-daad --limit=5  (test)
 *   php artisan programs:enrich-daad --throttle=2  (rate limit önlemi)
 */
class EnrichDaadPrograms extends Command
{
    protected $signature = 'programs:enrich-daad
        {--limit=0 : Sınırla (0=hepsi)}
        {--throttle=1 : İstekler arası bekleme (saniye)}
        {--lang=en : DAAD locale (en/de)}
        {--only-empty : Sadece description boş olan programları işle}';

    protected $description = 'DAAD detail page HTML scrape → canonical description/requirements doldur';

    public function handle(): int
    {
        $lang = $this->option('lang') === 'de' ? 'de' : 'en';
        $throttle = max(0, (int) $this->option('throttle'));
        $limit = (int) $this->option('limit');
        $onlyEmpty = (bool) $this->option('only-empty');

        // DAAD source linki olan program'ları al
        $query = ProgramSourceLink::query()
            ->where('source', 'daad')
            ->with('program');

        if ($onlyEmpty) {
            $query->whereHas('program', function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('description')->orWhere('description', '');
                });
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $links = $query->get();
        $total = $links->count();
        $this->info("İşlenecek DAAD program: {$total}");
        if ($total === 0) return self::SUCCESS;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $enriched = 0;
        $skipped = 0;
        $errors = 0;
        $start = microtime(true);

        foreach ($links as $i => $link) {
            try {
                if (! $link->program) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $url = "https://www2.daad.de/deutschland/studienangebote/international-programmes/{$lang}/detail/{$link->external_id}/";
                $resp = Http::timeout(15)->get($url);
                if (! $resp->successful()) {
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $fields = $this->parseDetailHtml($resp->body());
                if (empty($fields)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $update = $this->buildProgramUpdate($link->program, $fields);
                if (! empty($update)) {
                    $update['updated_at'] = now();
                    DB::table('programs')->where('id', $link->program->id)->update($update);
                    $enriched++;
                }
            } catch (\Throwable $e) {
                $errors++;
                if ($errors < 3) {
                    $this->newLine();
                    $this->warn("⚠ Hata #{$errors} (id={$link->external_id}): " . substr($e->getMessage(), 0, 100));
                }
                Log::warning('EnrichDaad.row_failed', [
                    'external_id' => $link->external_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();

            // Throttle: rate limit önlemi
            if ($throttle > 0 && $i + 1 < $total) {
                usleep($throttle * 100000); // throttle * 0.1 sn
            }
        }

        $bar->finish();
        $elapsed = (int) (microtime(true) - $start);
        $this->newLine(2);
        $this->info('✅ Enrichment tamamlandı:');
        $this->table(['Metric', 'Count'], [
            ['Description doldurulan', $enriched],
            ['Veri yok / boş', $skipped],
            ['Hata', $errors],
            ['Toplam', $total],
            ['Süre (sn)', $elapsed],
        ]);

        // Quality score recompute (background-friendly: tek SQL ile)
        $this->info('Quality score yeniden hesaplanıyor...');
        $this->recomputeQualityScores($links->pluck('program_id')->all());

        return self::SUCCESS;
    }

    /**
     * DAAD detail HTML → field map.
     * Pattern: <dt>Label</dt><dd>Value</dd>
     */
    private function parseDetailHtml(string $html): array
    {
        $pattern = '#<dt[^>]*>\s*(.*?)\s*</dt>\s*<dd[^>]*>\s*(.*?)\s*</dd>#sui';
        if (! preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $fields = [];
        foreach ($matches as $m) {
            $label = trim(strip_tags($m[1]));
            $label = preg_replace('/\s+/', ' ', $label);
            $value = trim($m[2]);
            $value = preg_replace('/\s+/', ' ', $value);
            if (mb_strlen(strip_tags($value)) < 10) continue;

            if (isset($fields[$label])) {
                $fields[$label] .= "\n\n" . $value;
            } else {
                $fields[$label] = $value;
            }
        }
        return $fields;
    }

    /** Field map → canonical Program update array. Sadece BOŞ alanları doldur. */
    private function buildProgramUpdate(Program $program, array $fields): array
    {
        $update = [];

        // Description: ana alan
        $desc = $this->extractField($fields, ['Description/content', 'Course content', 'Course objectives']);
        if ($desc && empty($program->description)) {
            $update['description'] = $this->cleanText($desc, 5000);
        }

        // Qualification requirements: Target group + Language requirements
        $qualParts = [];
        if ($v = $this->extractField($fields, ['Target group'])) {
            $qualParts[] = "Target group: " . $this->cleanText($v, 1000);
        }
        if ($v = $this->extractField($fields, ['Language requirements'])) {
            $qualParts[] = "Language: " . $this->cleanText($v, 800);
        }
        if (! empty($qualParts) && empty($program->qualification_requirements)) {
            $update['qualification_requirements'] = implode("\n\n", $qualParts);
        }

        // Language requirements ayrıca
        if ($v = $this->extractField($fields, ['Language requirements', 'Language level of course'])) {
            if (empty($program->language_requirements)) {
                $update['language_requirements'] = $this->cleanText($v, 1500);
            }
        }

        // Required documents = Application section + Submit application to
        $appParts = [];
        if ($v = $this->extractField($fields, ['Submit application to'])) {
            $appParts[] = "Apply to: " . $this->cleanText($v, 500);
        }
        if ($v = $this->extractField($fields, ['Application'])) {
            $appParts[] = $this->cleanText($v, 1500);
        }
        if (! empty($appParts) && empty($program->required_documents)) {
            $update['required_documents'] = implode("\n\n", $appParts);
        }

        return $update;
    }

    /** Field map'ten ilk eşleşen label'ı bul. */
    private function extractField(array $fields, array $needles): ?string
    {
        foreach ($needles as $needle) {
            foreach ($fields as $label => $value) {
                if (stripos($label, $needle) !== false) {
                    return $value;
                }
            }
        }
        return null;
    }

    /** HTML temizle, max len kontrol. */
    private function cleanText(string $value, int $maxLen): string
    {
        $text = strip_tags($value);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        if (mb_strlen($text) > $maxLen) {
            $text = mb_substr($text, 0, $maxLen) . '…';
        }
        return $text;
    }

    /** Etkilenen programları model üzerinden quality_score recompute. */
    private function recomputeQualityScores(array $programIds): void
    {
        $count = 0;
        foreach (array_chunk($programIds, 100) as $chunk) {
            Program::query()->whereIn('id', $chunk)->each(function (Program $p) use (&$count) {
                $p->recomputeQualityScore();
                $p->saveQuietly();
                $count++;
            });
        }
        $this->info("Quality score recompute: {$count} program");
    }
}
