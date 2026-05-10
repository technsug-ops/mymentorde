<?php

namespace App\Console\Commands;

use App\Models\Marketing\CmsContent;
use App\Services\Marketing\WikipediaImageFetcher;
use Illuminate\Console\Command;

/**
 * Kapağı olmayan blog yazılarına Wikipedia'dan uygun bir kapak görseli ata.
 *
 * Akış (her blog için sırayla):
 *   1. Title bazlı özel mapping (city-content, uni-content, popüler konseptler) → DE Wikipedia
 *   2. Title bazlı naive parse (ilk 1-2 kelime) → DE Wikipedia
 *   3. Kategori default cover'ı (komut başında 1 kez çekilir)
 *   4. Hiçbiri olmazsa atla
 *
 * Önbellek: aynı Wikipedia başlığı tekrar çekilmez (in-memory cache).
 *
 * Örnek:
 *   php artisan cms:fill-missing-covers
 *   php artisan cms:fill-missing-covers --category=careers
 *   php artisan cms:fill-missing-covers --dry-run
 */
class FillMissingCovers extends Command
{
    protected $signature = 'cms:fill-missing-covers
                            {--category= : Sadece belirli bir kategori için çalıştır}
                            {--force : Mevcut kapağı olan blog\'ları da yeniden çek}
                            {--dry-run : DB\'ye yazma, sadece neyin yapılacağını göster}';

    protected $description = 'Kapağı olmayan blog yazılarına Wikipedia\'dan otomatik kapak görseli ata';

    /**
     * Kategori → DE Wikipedia başlığı (default cover için).
     * Her kategoriye 1 ikonik görsel atanır, blog'lar paylaşır.
     */
    private const CATEGORY_DEFAULTS = [
        'careers'         => 'Großraumbüro',
        'culture-fun'     => 'Oktoberfest',
        'tips-tricks'     => 'Schreibtisch',
        'student-life'    => 'Universitätsbibliothek',
        'success-stories' => 'Studentin',
        'city-content'    => 'Reichstagsgebäude',
        'uni-content'     => 'Universität Heidelberg',
        'blog'            => 'Studium',
        'rehber'          => 'Deutscher Reisepass',
        'duyuru'          => 'Hörsaal',
        'kurumsal'        => 'Bildung in Deutschland',
    ];

    /**
     * Title pattern → Wikipedia başlığı override.
     * Sırayla denenir, ilk match alınır. Daha spesifik pattern'i önce yaz.
     */
    private const TITLE_OVERRIDES = [
        // Şehirler (city-content) — şehir adı tespiti
        '/\b(Berlin)\b/i'        => 'Berlin',
        '/\b(München|Munich)\b/i' => 'München',
        '/\b(Hamburg)\b/i'       => 'Hamburg',
        '/\b(Frankfurt)\b/i'     => 'Frankfurt am Main',
        '/\b(Köln|Koeln|Cologne)\b/i' => 'Köln',
        '/\b(Stuttgart)\b/i'     => 'Stuttgart',
        '/\b(Düsseldorf|Duesseldorf)\b/i' => 'Düsseldorf',
        '/\b(Heidelberg)\b/i'    => 'Heidelberg',
        '/\b(Aachen)\b/i'        => 'Aachen',
        '/\b(Bonn)\b/i'          => 'Bonn',
        '/\b(Hannover|Hanover)\b/i' => 'Hannover',
        '/\b(Leipzig|Leipzip)\b/i' => 'Leipzig',
        '/\b(Dresden)\b/i'       => 'Dresden',
        '/\b(Freiburg)\b/i'      => 'Freiburg im Breisgau',
        '/\b(Braunschweig)\b/i'  => 'Braunschweig',

        // Üniversiteler (uni-content) — kalanları
        '/Humboldt Berlin/i'     => 'Humboldt-Universität zu Berlin',
        '/HAW Hamburg/i'         => 'Hochschule für Angewandte Wissenschaften Hamburg',
        '/TH Köln/i'             => 'Technische Hochschule Köln',
        '/DHBW Stuttgart/i'      => 'Duale Hochschule Baden-Württemberg Stuttgart',
        '/Heidelberg Uygulamalı/i' => 'SRH Hochschule Heidelberg',

        // Konseptler (tips-tricks, culture-fun, student-life)
        '/Oktoberfest/i'         => 'Oktoberfest',
        '/Karnaval/i'            => 'Kölner Karneval',
        '/Schwarzwald/i'         => 'Schwarzwald',
        '/Rhine|Rhein/i'         => 'Rhein',
        '/Triberg/i'             => 'Triberger Wasserfälle',
        '/Hofbräuhaus|Hofbrauhaus/i' => 'Hofbräuhaus München',
        '/English Garden/i'      => 'Englischer Garten',
        '/Anmeldung/i'           => 'Meldepflicht',
        '/Sperrkonto/i'          => 'Sperrkonto',
        '/BahnCard/i'            => 'BahnCard',
        '/BAföG/i'               => 'BAföG',
        '/DAAD/i'                => 'Deutscher Akademischer Austauschdienst',
        '/Erasmus/i'             => 'Erasmus-Programm',
        '/Mensa/i'               => 'Mensa (Studentenwerk)',
        '/Marienplatz/i'         => 'Marienplatz',
        '/Brandenburger Tor/i'   => 'Brandenburger Tor',
        '/Frauenkirche/i'        => 'Frauenkirche Dresden',
        '/Münster|Muenster/i'    => 'Münster',
        '/Sperrkonto/i'          => 'Sperrkonto',
        '/Vize|Visum/i'          => 'Visum',
        '/CV|Lebenslauf/i'       => 'Lebenslauf',

        // Sektör/Kariyer
        '/Yazılım/i'             => 'Softwareentwicklung',
        '/Makine Mühendisliği/i' => 'Maschinenbau',
        '/Veri Bilimi/i'         => 'Data Science',
        '/Kimya Mühendisliği/i'  => 'Chemieingenieurwesen',
        '/Tıbbi Araştırma/i'     => 'Medizinische Forschung',
        '/Finans/i'              => 'Bank',
        '/Pazarlama/i'           => 'Marketing',
        '/Lojistik/i'            => 'Logistik',
        '/Enerji/i'              => 'Energiewirtschaft',
        '/Çevre/i'               => 'Nachhaltigkeit',
    ];

    /** @var array<string, array> in-memory cache: $wikipediaTitle => $fetchResult */
    private array $wikiCache = [];

    public function handle(WikipediaImageFetcher $fetcher): int
    {
        $onlyCategory = (string) ($this->option('category') ?? '');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        // 1) Kategori default cover'larını çek
        $this->info('Kategori default cover\'ları çekiliyor...');
        $categoryDefaults = [];
        foreach (self::CATEGORY_DEFAULTS as $cat => $wikiTitle) {
            if ($onlyCategory !== '' && $onlyCategory !== $cat) continue;
            $res = $this->fetchCached($fetcher, $wikiTitle);
            if ($res['ok']) {
                $categoryDefaults[$cat] = $res;
                $this->line("  ✓ {$cat} → " . basename((string) $res['path']));
            } else {
                $this->warn("  ✗ {$cat} → {$wikiTitle} çekilemedi: " . ($res['message'] ?? '?'));
            }
        }
        $this->newLine();

        // 2) Kapaksız (veya --force ile tüm) blog'ları çek
        $query = CmsContent::query()->where('status', 'published');
        if (!$force) {
            $query->where(function ($q) { $q->whereNull('cover_image_url')->orWhere('cover_image_url', ''); });
        }
        if ($onlyCategory !== '') $query->where('category', $onlyCategory);
        $rows = $query->orderBy('id')->get();

        $this->info(($force ? 'Hedef blog sayısı (force): ' : 'Kapaksız blog sayısı: ') . $rows->count());
        if ($rows->isEmpty()) return self::SUCCESS;

        $titleHit = 0; $defaultHit = 0; $fail = 0;
        foreach ($rows as $row) {
            $cover = null;
            $source = '';

            // 2a) Title pattern override
            foreach (self::TITLE_OVERRIDES as $pattern => $wikiTitle) {
                if (preg_match($pattern, $row->title_tr)) {
                    $cover = $this->fetchCached($fetcher, $wikiTitle);
                    if ($cover['ok']) {
                        $source = "title→{$wikiTitle}";
                        break;
                    }
                    $cover = null;
                }
            }

            // 2b) Kategori default
            if (!$cover && isset($categoryDefaults[$row->category])) {
                $cover = $categoryDefaults[$row->category];
                $source = "category-default:{$row->category}";
            }

            if (!$cover || !($cover['ok'] ?? false)) {
                $this->error("✗ #{$row->id} '{$row->title_tr}' — uygun cover bulunamadı (category={$row->category})");
                $fail++;
                continue;
            }

            $isTitleHit = str_starts_with($source, 'title→');
            $tag = $isTitleHit ? '[TITLE]' : '[DEFAULT]';
            $this->line("{$tag} #" . str_pad($row->id, 4) . " {$row->title_tr}");
            $this->line("        → {$source}");

            if (!$dryRun) {
                $row->cover_image_url = $cover['url'];
                if (empty($row->cover_image_alt)) {
                    $row->cover_image_alt = $cover['attribution'] ?? null;
                }
                $row->save();
            }

            $isTitleHit ? $titleHit++ : $defaultHit++;
        }

        $this->newLine();
        $this->info("Tamamlandı: {$titleHit} title-bazlı, {$defaultHit} kategori-default, {$fail} başarısız.");
        if ($dryRun) $this->warn('--dry-run aktif, DB değişmedi.');
        return self::SUCCESS;
    }

    private function fetchCached(WikipediaImageFetcher $fetcher, string $title): array
    {
        $key = mb_strtolower($title);
        if (isset($this->wikiCache[$key])) {
            return $this->wikiCache[$key];
        }
        $result = $fetcher->fetch($title);
        $this->wikiCache[$key] = $result;
        if ($result['ok'] ?? false) {
            usleep(500_000); // 0.5s nezaket — Wikipedia API rate limit
        }
        return $result;
    }
}
