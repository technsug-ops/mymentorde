<?php

namespace App\Console\Commands;

use App\Models\Marketing\CmsContent;
use App\Services\Marketing\WikipediaImageFetcher;
use Illuminate\Console\Command;

/**
 * cms_contents tablosundaki üniversite blog yazılarına Wikipedia'dan
 * kapak görseli çek + cover_image_url + cover_image_alt'ı doldur.
 *
 * Slug-bazlı mapping: her üniversite post'u için doğru Wikipedia başlığını verir
 * (title_tr "TU Munich — Başvuru Rehberi" Wikipedia'da bulunamaz, doğru başlık
 * "Technische Universität München").
 *
 * Örnek:
 *   php artisan cms:fetch-university-covers           # boş kapak'lı tüm uni post'ları
 *   php artisan cms:fetch-university-covers --force   # zaten kapak'ı olanları da çek
 *   php artisan cms:fetch-university-covers --slug=tu-munich-basvuru-rehberi
 */
class FetchUniversityCovers extends Command
{
    protected $signature = 'cms:fetch-university-covers
                            {--force : Zaten kapak görseli olan post\'ları da yeniden çek}
                            {--slug= : Sadece belirli bir slug için çalıştır}';

    protected $description = 'Üniversite blog yazılarına Wikipedia\'dan kapak görseli çek';

    /**
     * cms_contents.slug → Wikipedia (DE) başlığı.
     * Yeni üniversite blog'u eklendiğinde bu mapping'e satır ekle.
     */
    private const SLUG_TO_WIKI = [
        'tu-munich-rehberi'              => 'Technische Universität München',
        'lmu-munich-rehberi'             => 'Ludwig-Maximilians-Universität München',
        'heidelberg-rehberi'             => 'Ruprecht-Karls-Universität Heidelberg',
        'bonn-rehberi'                   => 'Rheinische Friedrich-Wilhelms-Universität Bonn',
        'hamburg-rehberi'                => 'Universität Hamburg',
        'goethe-universitesi-frankfurt'  => 'Goethe-Universität Frankfurt am Main',
        'tu-darmstadt-rehberi'           => 'Technische Universität Darmstadt',
        'hochschule-munchen-rehberi'     => 'Hochschule München',
        'universitat-konstanz-rehberi'   => 'Universität Konstanz',
        'tu-bergakademie-rehberi'        => 'TU Bergakademie Freiberg',
    ];

    public function handle(WikipediaImageFetcher $fetcher): int
    {
        $force = (bool) $this->option('force');
        $onlySlug = (string) ($this->option('slug') ?? '');

        $rows = CmsContent::query()
            ->whereIn('slug', array_keys(self::SLUG_TO_WIKI))
            ->when($onlySlug !== '', fn ($q) => $q->where('slug', $onlySlug))
            ->when(!$force && $onlySlug === '', fn ($q) => $q->whereNull('cover_image_url')->orWhere('cover_image_url', ''))
            ->get();

        if ($rows->isEmpty()) {
            $this->info('İşlenecek post yok (--force ile zorla, ya da --slug ile spesifik bir post).');
            return self::SUCCESS;
        }

        $this->info('İşlenecek post sayısı: ' . $rows->count());
        $ok = 0; $fail = 0;
        foreach ($rows as $row) {
            $wikiTitle = self::SLUG_TO_WIKI[$row->slug] ?? null;
            if ($wikiTitle === null) {
                $this->warn("• #{$row->id} {$row->slug} — mapping yok, atlandı");
                continue;
            }
            $this->line("• #{$row->id} {$row->slug} → {$wikiTitle}");
            $res = $fetcher->fetch($wikiTitle);
            if (!($res['ok'] ?? false)) {
                $this->error('  ✗ ' . ($res['message'] ?? 'Bilinmeyen hata'));
                $fail++;
                continue;
            }
            $row->cover_image_url = $res['url'];
            if (empty($row->cover_image_alt)) {
                $row->cover_image_alt = $res['attribution'] ?? '';
            }
            $row->save();
            $this->info('  ✓ ' . ($res['lang'] ?? '') . ' wiki — ' . basename((string) $res['path']));
            $ok++;
            usleep(500_000); // 0.5s — Wikipedia API'sini boğmamak için nezaket beklemesi
        }

        $this->newLine();
        $this->info("Tamamlandı: {$ok} başarılı, {$fail} başarısız.");
        return self::SUCCESS;
    }
}
