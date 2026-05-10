<?php

namespace App\Console\Commands;

use App\Models\Marketing\CmsContent;
use App\Services\AiLabs\GeminiProvider;
use App\Services\Marketing\WikipediaImageFetcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Top Almanya üniversiteleri için draft blog yazısı üret:
 *   1. Wikipedia DE summary çek (gerçek factual veri)
 *   2. Gemini'ye TR prompt ile besle → ~600 kelime blog (HTML)
 *   3. WikipediaImageFetcher ile kapak görseli çek
 *   4. cms_contents'a status=draft kaydet
 *
 * Yayınlanmaz — sen review edip publish edeceksin.
 *
 * Örnek:
 *   php artisan cms:generate-university-blogs              # tüm 20 üni
 *   php artisan cms:generate-university-blogs --limit=3    # ilk 3 (test)
 *   php artisan cms:generate-university-blogs --slug=fu-berlin-rehberi
 *   php artisan cms:generate-university-blogs --dry-run    # gerçek yaratmadan test
 */
class GenerateUniversityBlogs extends Command
{
    protected $signature = 'cms:generate-university-blogs
                            {--limit=20 : Üretilecek maks blog sayısı}
                            {--slug= : Sadece belirli bir slug için çalıştır}
                            {--dry-run : Wikipedia/Gemini çağrısı yap ama DB\'ye yazma}
                            {--force : Aynı slug zaten varsa üzerine yaz}';

    protected $description = 'Top Alman üniversiteleri için draft blog yazıları üret (Wikipedia + Gemini)';

    /**
     * 20 yeni üniversite — mevcut 10 (TUM/LMU/Heidelberg/Bonn/Hamburg/Goethe Frankfurt/
     * TU Darmstadt/Hochschule München/Konstanz/TU Bergakademie Freiberg) hariç tutuldu.
     *
     * Format: slug => [tr_title_short, wikipedia_de_title]
     */
    private const UNIVERSITIES = [
        'fu-berlin-rehberi'           => ['FU Berlin', 'Freie Universität Berlin'],
        'hu-berlin-rehberi'           => ['HU Berlin', 'Humboldt-Universität zu Berlin'],
        'tu-berlin-rehberi'           => ['TU Berlin', 'Technische Universität Berlin'],
        'rwth-aachen-rehberi'         => ['RWTH Aachen', 'RWTH Aachen'],
        'koln-universitesi-rehberi'   => ['Köln Üniversitesi', 'Universität zu Köln'],
        'bochum-universitesi-rehberi' => ['Bochum Üniversitesi', 'Ruhr-Universität Bochum'],
        'stuttgart-universitesi-rehberi' => ['Stuttgart Üniversitesi', 'Universität Stuttgart'],
        'munster-universitesi-rehberi' => ['Münster Üniversitesi', 'Westfälische Wilhelms-Universität Münster'],
        'wurzburg-universitesi-rehberi' => ['Würzburg Üniversitesi', 'Julius-Maximilians-Universität Würzburg'],
        'tubingen-universitesi-rehberi' => ['Tübingen Üniversitesi', 'Eberhard Karls Universität Tübingen'],
        'mannheim-universitesi-rehberi' => ['Mannheim Üniversitesi', 'Universität Mannheim'],
        'erlangen-nurnberg-rehberi'   => ['Erlangen-Nürnberg', 'Friedrich-Alexander-Universität Erlangen-Nürnberg'],
        'kit-karlsruhe-rehberi'       => ['KIT Karlsruhe', 'Karlsruher Institut für Technologie'],
        'tu-dresden-rehberi'          => ['TU Dresden', 'Technische Universität Dresden'],
        'leipzig-universitesi-rehberi' => ['Leipzig Üniversitesi', 'Universität Leipzig'],
        'gottingen-universitesi-rehberi' => ['Göttingen Üniversitesi', 'Georg-August-Universität Göttingen'],
        'mainz-universitesi-rehberi'  => ['Mainz Üniversitesi', 'Johannes Gutenberg-Universität Mainz'],
        'marburg-universitesi-rehberi' => ['Marburg Üniversitesi', 'Philipps-Universität Marburg'],
        'jena-universitesi-rehberi'   => ['Jena Üniversitesi', 'Friedrich-Schiller-Universität Jena'],
        'bayreuth-universitesi-rehberi' => ['Bayreuth Üniversitesi', 'Universität Bayreuth'],
    ];

    public function handle(GeminiProvider $gemini, WikipediaImageFetcher $imageFetcher): int
    {
        $limit = (int) $this->option('limit');
        $onlySlug = (string) ($this->option('slug') ?? '');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        // created_by/last_edited_by için ilk manager — cms_contents tablosu NOT NULL
        $authorId = (int) (\App\Models\User::query()->where('role', 'manager')->orWhere('role', 'admin_staff')->value('id') ?? 1);

        $list = self::UNIVERSITIES;
        if ($onlySlug !== '') {
            $list = array_intersect_key($list, [$onlySlug => true]);
            if (empty($list)) {
                $this->error("Slug bulunamadı: {$onlySlug}");
                return self::FAILURE;
            }
        }

        $count = 0;
        $ok = 0;
        $skip = 0;
        $fail = 0;

        foreach ($list as $slug => [$shortTitle, $wikiTitle]) {
            if ($count >= $limit) break;
            $count++;

            $this->newLine();
            $this->line("=== [{$count}] {$shortTitle} → {$wikiTitle} ===");

            if (!$force && CmsContent::query()->where('slug', $slug)->exists()) {
                $this->warn("  [skip] slug '{$slug}' zaten var (--force ile üzerine yaz)");
                $skip++;
                continue;
            }

            $summary = $this->fetchWikipediaSummary($wikiTitle);
            if ($summary === null) {
                $this->error("  [fail] Wikipedia summary alınamadı");
                $fail++;
                continue;
            }
            $this->info('  ✓ Wikipedia summary: ' . mb_substr($summary['extract'], 0, 80) . '...');

            $blog = $this->generateBlog($gemini, $shortTitle, $wikiTitle, $summary);
            if ($blog === null) {
                $this->error("  [fail] Gemini blog üretemedi");
                $fail++;
                continue;
            }
            $this->info('  ✓ Gemini blog: ' . mb_strlen($blog['content_tr']) . ' karakter, başlık: ' . $blog['title_tr']);

            if ($dryRun) {
                $this->warn('  [dry-run] DB\'ye yazılmadı');
                $ok++;
                continue;
            }

            $coverUrl = null;
            $coverAlt = null;
            $coverRes = $imageFetcher->fetch($wikiTitle);
            if ($coverRes['ok'] ?? false) {
                $coverUrl = $coverRes['url'];
                $coverAlt = $coverRes['attribution'] ?? null;
                $this->info('  ✓ Cover: ' . basename((string) $coverRes['path']));
            } else {
                $this->warn('  [warn] Cover alınamadı: ' . ($coverRes['message'] ?? '?'));
            }

            $row = CmsContent::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => 'guide',
                    'title_tr' => $blog['title_tr'],
                    'summary_tr' => $blog['summary_tr'],
                    'content_tr' => $blog['content_tr'],
                    'cover_image_url' => $coverUrl,
                    'cover_image_alt' => $coverAlt,
                    'status' => 'draft',
                    'category' => 'uni-content',
                    'target_audience' => 'all',
                    'current_revision' => 1,
                    'created_by' => $authorId,
                    'last_edited_by' => $authorId,
                ]
            );
            $this->info("  ✓ DB kaydedildi: cms_contents #{$row->id} (status=draft)");
            $ok++;

            sleep(1); // API'leri boğmamak için
        }

        $this->newLine();
        $this->info("Tamamlandı: {$ok} başarılı, {$skip} atlandı, {$fail} başarısız.");
        if ($ok > 0 && !$dryRun) {
            $this->line("Review için: <comment>/mktg-admin/content?status=draft</comment>");
        }
        return self::SUCCESS;
    }

    /**
     * Wikipedia DE summary REST API → factual base.
     *
     * @return array{title:string,extract:string,description:string}|null
     */
    private function fetchWikipediaSummary(string $title): ?array
    {
        try {
            $resp = Http::withHeaders([
                'User-Agent' => 'MentorDE/1.0 (https://panel.mentorde.com; technsug@gmail.com)',
                'Accept' => 'application/json',
            ])->timeout(15)->get('https://de.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode($title));
        } catch (\Throwable $e) {
            return null;
        }
        if (!$resp->ok()) return null;
        $data = $resp->json();
        $extract = (string) data_get($data, 'extract', '');
        if ($extract === '') return null;
        return [
            'title' => (string) data_get($data, 'title', $title),
            'extract' => $extract,
            'description' => (string) data_get($data, 'description', ''),
        ];
    }

    /**
     * Gemini → TR blog (HTML body + başlık + özet).
     *
     * @return array{title_tr:string,summary_tr:string,content_tr:string}|null
     */
    private function generateBlog(GeminiProvider $gemini, string $shortTitle, string $wikiTitle, array $summary): ?array
    {
        $systemPrompt = <<<'TXT'
Sen MentorDE adlı Almanya üniversite başvuru danışmanlık platformu için Türkçe blog yazıları yazan bir editörsün.
Hedef kitle: Almanya'da lisans/yüksek lisans okumak isteyen Türk öğrenciler.
Tonlama: profesyonel ama samimi, "siz" hitabı, jargon yok, Türk öğrencinin kafasındaki gerçek soruları yanıtla.
Format: HTML — sadece <h2>, <h3>, <p>, <ul>/<li>, <strong> kullan; başka tag yok.
Asla uydurma: kesin tarih, ücret, deadline iddia etme. "Resmi siteden teyit edin" disclaimer'ı eklemeyi unutma.
ASLA <html>, <body>, <head> dahil etme — sadece içerik HTML'i.
Çıktı formatı KESİNLİKLE şu JSON yapısında olmalı (JSON dışı hiçbir metin yazma):
{"title_tr": "...", "summary_tr": "...", "content_tr": "..."}
TXT;

        $userPrompt = <<<TXT
Üniversite: {$wikiTitle}
Kısa ad: {$shortTitle}
Wikipedia DE özeti: {$summary['extract']}

Bu üniversite hakkında Türk öğrenciler için yaklaşık 600 kelimelik bir blog yazısı üret.

İçerik şu bölümleri kapsasın:
1. Giriş paragrafı — üniversitenin Almanya'daki yeri ve önemi (kısa)
2. <h2>Kuruluş ve Tarihsel Önemi</h2> — Wikipedia'dan aldığın gerçek bilgiler
3. <h2>Akademik Yapı</h2> — fakülteler/güçlü oldukları alanlar (Wikipedia'ya dayalı)
4. <h2>Türk Öğrenciler İçin Neden Tercih Edilir?</h2> — şehir/bölge avantajları, yaşam, lisan imkanları (genel olarak Almanya gerçeklerine dayan)
5. <h2>Başvuru Sürecinde Dikkat Edilecekler</h2> — Uni-Assist gerekliliği, dil sertifikası (TestDaF/DSH), motivasyon mektubu (genel ipuçları, spesifik tarih VERME, "{$shortTitle} resmi sitesinden güncel bilgiyi teyit edin" de)
6. <h2>Sonuç</h2> — kısa kapanış, MentorDE'nin başvuru sürecinde nasıl yardımcı olabileceğine dair 1-2 cümle

Başlık (title_tr): "{$shortTitle} — Türk Öğrenciler İçin Başvuru Rehberi" formatında ya da daha çekici bir varyasyonu.
Özet (summary_tr): 1-2 cümle, ~150 karakter.
İçerik (content_tr): yukarıdaki HTML yapısı.

KESİNLİKLE JSON formatında dön, başka açıklama yazma.
TXT;

        $res = $gemini->chat($systemPrompt, $userPrompt, [], [
            'temperature' => 0.7,
            'max_output_tokens' => 4096,
            'response_mime_type' => 'application/json',
        ], 1);

        if (!($res['ok'] ?? false)) {
            $this->warn('  Gemini error: ' . ($res['error'] ?? 'unknown'));
            return null;
        }

        $raw = (string) ($res['content'] ?? '');
        $raw = trim($raw);
        // Bazen markdown code fence ile geliyor
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw);
        $parsed = json_decode($raw, true);
        if (!is_array($parsed) || empty($parsed['content_tr'])) {
            $this->warn('  Gemini JSON parse failed. Raw: ' . mb_substr($raw, 0, 200));
            return null;
        }
        return [
            'title_tr' => (string) ($parsed['title_tr'] ?? "{$shortTitle} — Başvuru Rehberi"),
            'summary_tr' => (string) ($parsed['summary_tr'] ?? ''),
            'content_tr' => (string) $parsed['content_tr'],
        ];
    }
}
