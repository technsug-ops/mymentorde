<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Markdown el kitaplarını public/handbooks/*.html olarak yeniden üretir.
 *
 * Kaynak MD dosyaları tek doğruluk kaynağıdır; HTML çıktıları elle düzenlenmez.
 * Commit öncesi `php artisan handbook:build` (veya CI'da `--check`) ile senkron tutulur.
 */
class BuildHandbooksCommand extends Command
{
    protected $signature = 'handbook:build {--check : Üretme, sadece HTML çıktıları güncel mi kontrol et}';

    protected $description = 'HANDBOOK_TR/EN + DEV_HANDBOOK markdown dosyalarından public/handbooks/*.html üretir';

    /** @var list<array{src:string,out:string,lang:string,title:string,footer:string}> */
    private const DOCS = [
        [
            'src'    => 'HANDBOOK_TR.md',
            'out'    => 'HANDBOOK_tr.html',
            'lang'   => 'tr',
            'title'  => 'MentorDE Kullanıcı Kılavuzu',
            'footer' => 'MentorDE &copy; %d — MentorDE Kullanıcı Kılavuzu',
        ],
        [
            'src'    => 'HANDBOOK_EN.md',
            'out'    => 'HANDBOOK_en.html',
            'lang'   => 'en',
            'title'  => 'MentorDE Handbook',
            'footer' => 'MentorDE &copy; %d — MentorDE Handbook',
        ],
        [
            'src'    => 'DEV_HANDBOOK.md',
            'out'    => 'DEV_HANDBOOK.html',
            'lang'   => 'tr',
            'title'  => 'MentorDE Developer Handbook',
            'footer' => 'MentorDE Developer Handbook &copy; %d',
        ],
    ];

    /** Mevcut HTML'lerdeki stil — görünüm birebir korunsun diye aynen taşındı. */
    private const STYLE = <<<'CSS'
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f5f7fb;color:#1a2233;padding:40px 20px;line-height:1.75;}
.wrap{max-width:900px;margin:0 auto;background:#fff;border-radius:12px;padding:48px 56px;box-shadow:0 4px 32px rgba(0,0,0,.08);}
h1,h2{font-size:1.3rem;font-weight:800;color:#1f66d1;margin:2.5rem 0 .6rem;padding-bottom:.4rem;border-bottom:2px solid #e2e8f0;}
h3{font-size:1.05rem;font-weight:700;color:#1a2233;margin:1.8rem 0 .5rem;}
h4{font-size:.95rem;font-weight:600;color:#4b5563;margin:1.3rem 0 .4rem;}
p{margin-bottom:.8rem;}ul,ol{margin:.4rem 0 1rem 1.6rem;}li{margin-bottom:.3rem;}
table{width:100%;border-collapse:collapse;margin:1rem 0;font-size:.9rem;}
th{background:#1f66d1;color:#fff;padding:9px 12px;text-align:left;font-weight:600;}
td{padding:8px 12px;border-bottom:1px solid #e2e8f0;}
tr:nth-child(even) td{background:#f8faff;}
code{background:#f1f5f9;border:1px solid #e2e8f0;padding:2px 6px;border-radius:4px;font-size:.85rem;}
pre{background:#f1f5f9;padding:16px;border-radius:8px;overflow-x:auto;margin:1rem 0;}
blockquote{border-left:3px solid #1f66d1;padding-left:16px;color:#6b7280;margin:1rem 0;}
hr{border:none;border-top:1px solid #e2e8f0;margin:2rem 0;}
.footer{text-align:center;margin-top:48px;padding-top:24px;border-top:1px solid #e2e8f0;font-size:.82rem;color:#9ca3af;}
@media print{body{background:#fff;padding:0;} .wrap{box-shadow:none;padding:24px;}}
CSS;

    public function handle(): int
    {
        $outDir = public_path('handbooks');
        if (!is_dir($outDir) && !$this->option('check')) {
            mkdir($outDir, 0755, true);
        }

        $check  = (bool) $this->option('check');
        $stale  = [];
        $missing = [];

        foreach (self::DOCS as $doc) {
            $srcPath = base_path($doc['src']);
            if (!is_file($srcPath)) {
                $missing[] = $doc['src'];
                $this->error("  kaynak yok: {$doc['src']}");
                continue;
            }

            $html    = $this->render((string) file_get_contents($srcPath), $doc);
            $outPath = $outDir . DIRECTORY_SEPARATOR . $doc['out'];
            $current = is_file($outPath) ? (string) file_get_contents($outPath) : null;

            if ($check) {
                if ($current !== $html) {
                    $stale[] = $doc['out'];
                    $this->warn("  BAYAT  {$doc['out']}  ← {$doc['src']}");
                } else {
                    $this->line("  guncel {$doc['out']}");
                }
                continue;
            }

            file_put_contents($outPath, $html);
            $this->info(sprintf('  %-20s %s (%d KB)', $doc['src'], '→ handbooks/' . $doc['out'], (int) round(strlen($html) / 1024)));
        }

        if ($missing !== []) {
            return self::FAILURE;
        }

        if ($check && $stale !== []) {
            $this->newLine();
            $this->error('HTML el kitapları bayat. Düzeltmek için: php artisan handbook:build');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array{src:string,out:string,lang:string,title:string,footer:string}  $doc
     */
    private function render(string $markdown, array $doc): string
    {
        $body = $this->addHeadingIds(Str::markdown($markdown));
        $footer = sprintf($doc['footer'], (int) date('Y'));

        return '<!doctype html><html lang="' . $doc['lang'] . '"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . e($doc['title']) . '</title><style>' . "\n"
            . self::STYLE . "\n"
            . '</style></head><body><div class="wrap">' . "\n"
            . trim($body) . "\n"
            . '<div class="footer">' . $footer . '</div></div></body></html>';
    }

    /**
     * Başlıklara GitHub tarzı slug id'si ekler — içindekiler bölümündeki
     * `#1-sistem-genel-bakis` bağlantıları ancak bu id'lerle çalışır.
     */
    private function addHeadingIds(string $html): string
    {
        $seen = [];

        return (string) preg_replace_callback(
            '/<h([1-6])>(.*?)<\/h\1>/su',
            function (array $m) use (&$seen): string {
                $slug = $this->slug(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($slug === '') {
                    return $m[0];
                }
                $seen[$slug] = ($seen[$slug] ?? -1) + 1;
                $id = $seen[$slug] > 0 ? $slug . '-' . $seen[$slug] : $slug;

                return '<h' . $m[1] . ' id="' . $id . '">' . $m[2] . '</h' . $m[1] . '>';
            },
            $html
        );
    }

    /** GitHub başlık slug'ı: küçük harf, noktalama at, boşluk → tire (Türkçe harfler korunur). */
    private function slug(string $text): string
    {
        $s = mb_strtolower(trim($text), 'UTF-8');
        $s = (string) preg_replace('/[^\p{L}\p{N}\s-]+/u', '', $s);
        $s = (string) preg_replace('/\s+/u', '-', $s);

        return trim($s, '-');
    }
}
