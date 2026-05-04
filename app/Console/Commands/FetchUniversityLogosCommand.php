<?php

namespace App\Console\Commands;

use App\Models\University;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class FetchUniversityLogosCommand extends Command
{
    protected $signature = 'unimatch:fetch-uni-logos
                            {--limit=20 : Bu run\'da max kaç üni işlensin}
                            {--dry-run : Sadece raporla, indirme yapma}
                            {--lang=en : Wikipedia dili (en/de)}
                            {--throttle-ms=500 : Request arası ms bekleme (rate-limit)}';

    protected $description = 'Wikipedia API ile boş image_path\'li üniversitelere logo indirir';

    public function handle(): int
    {
        $limit    = max(1, (int) $this->option('limit'));
        $dry      = (bool) $this->option('dry-run');
        $lang     = (string) $this->option('lang');
        $throttle = max(0, (int) $this->option('throttle-ms')) / 1_000_000.0; // microseconds multiplier

        $unis = University::query()
            ->where(function ($q) {
                $q->whereNull('image_path')->orWhere('image_path', '');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $this->info("Adaylar: {$unis->count()}");

        $dir = public_path('img/uni-logos');
        if (! $dry && ! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $hit = 0;
        $miss = 0;

        foreach ($unis as $uni) {
            if ($throttle > 0) {
                usleep((int) ($throttle * 1_000_000));
            }

            try {
                $resp = Http::timeout(15)->get("https://{$lang}.wikipedia.org/w/api.php", [
                    'action'      => 'query',
                    'titles'      => $uni->name,
                    'prop'        => 'pageimages',
                    'piprop'      => 'thumbnail|original',
                    'pithumbsize' => 800,
                    'format'      => 'json',
                    'redirects'   => 1,
                ]);

                if (! $resp->successful()) {
                    $miss++;
                    $this->warn("  ⚠ {$uni->name}: HTTP {$resp->status()}");
                    continue;
                }

                $pages = data_get($resp->json(), 'query.pages');
                if (! is_array($pages) || empty($pages)) {
                    $miss++;
                    continue;
                }

                $page     = reset($pages);
                $thumbUrl = data_get($page, 'thumbnail.source')
                         ?: data_get($page, 'original.source');

                if (empty($thumbUrl)) {
                    $miss++;
                    $this->line("  × {$uni->name}: görsel yok");
                    continue;
                }

                if ($dry) {
                    $hit++;
                    $this->line("  [dry] ✓ {$uni->name} -> {$thumbUrl}");
                    continue;
                }

                $img = Http::timeout(30)->get($thumbUrl);
                if (! $img->successful()) {
                    $miss++;
                    $this->warn("  ⚠ indirme başarısız: {$uni->name}");
                    continue;
                }

                $ext = strtolower(
                    pathinfo(parse_url($thumbUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg'
                );
                if ($ext === 'jpeg') {
                    $ext = 'jpg';
                }
                if (! in_array($ext, ['jpg', 'png', 'webp', 'svg'], true)) {
                    $ext = 'jpg';
                }

                $filename = $uni->id . '.' . $ext;
                file_put_contents("{$dir}/{$filename}", $img->body());

                $uni->image_path = '/img/uni-logos/' . $filename;
                $uni->save();

                $hit++;
                $this->info("  ✓ {$uni->name} -> {$filename}");
            } catch (\Throwable $e) {
                $miss++;
                $this->warn("  ⚠ {$uni->name}: " . $e->getMessage());
            }
        }

        $this->info("\n✓ hit: {$hit}  × miss: {$miss}");
        if ($dry) {
            $this->warn('--dry-run aktif, indirme yapılmadı.');
        }

        return 0;
    }
}
