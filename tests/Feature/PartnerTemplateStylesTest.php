<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Şablon bölümlerinde KULLANILAN her sınıfın TANIMI olmalı.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Minimal şablonunda öğrenci yorumları bölümü `.q-grid`, `.q`, `.qm`, `.qw`
 * sınıflarını kullanıyordu ama hiçbiri tanımlı değildi. O şablonu seçen
 * bayi biçimsiz, üst üste yığılmış bir blok görüyordu ve bunu kimse fark
 * etmemişti — sayfa 200 dönüyor, test yeşil, görüntü bozuk.
 *
 * HTTP testi düzeni ölçemez; ama "kullanılan sınıfın karşılığı var mı"
 * sorusunu ölçebilir. Bu testin yakaladığı şey tam olarak o.
 */
class PartnerTemplateStylesTest extends TestCase
{
    private const ROOT = 'resources/views/public/partner-templates';

    /**
     * Şablona ait olmayan, ortak/yardımcı sınıflar.
     *
     * Bunlar ya global stil ya da yalnızca JS/markup işareti; şablon CSS'inde
     * karşılığı olmaması normal.
     *
     * @var list<string>
     */
    private const SHARED = [
        'wrap', 'container', 'sec', 'sec-top', 'sec-head', 'center', 'c',
        'acc', 'serif', 'eyebrow', 'kick', 'rule', 'sec-label', 'sec-title',
        'sec-lead', 'sec-bg-white', 'btn', 'btn-primary', 'btn-ghost',
    ];

    public function test_every_class_used_in_sections_is_defined(): void
    {
        $missing = [];

        foreach (glob(base_path(self::ROOT . '/*.blade.php')) as $templateFile) {
            $key = basename($templateFile, '.blade.php');

            $sectionDir = base_path(self::ROOT . '/' . $key . '/sections');

            if (!is_dir($sectionDir)) {
                continue;
            }

            $css = (string) file_get_contents($templateFile);

            foreach (glob($sectionDir . '/*.blade.php') as $sectionFile) {
                foreach ($this->classesUsedIn($sectionFile) as $class) {
                    if (in_array($class, self::SHARED, true)) {
                        continue;
                    }

                    // Sınıf CSS'te herhangi bir seçicide geçiyor mu?
                    if (!preg_match('/\.' . preg_quote($class, '/') . '\b/', $css)) {
                        $missing[$key][] = basename($sectionFile, '.blade.php') . ' → .' . $class;
                    }
                }
            }
        }

        $this->assertSame([], $missing, "Tanimsiz sinif(lar):\n" . $this->format($missing));
    }

    /**
     * Blade dosyasındaki statik class adları.
     *
     * Yalnızca sabit `class="..."` değerleri okunur; içinde {{ }} geçenler
     * atlanır çünkü değeri çalışma anında belirleniyor ve burada
     * çözülemez — yanlış alarm üretirdi.
     *
     * @return list<string>
     */
    private function classesUsedIn(string $file): array
    {
        $html = (string) file_get_contents($file);

        preg_match_all('/class="([^"]*)"/', $html, $matches);

        $classes = [];

        foreach ($matches[1] ?? [] as $value) {
            if (str_contains($value, '{{') || str_contains($value, '@')) {
                continue;
            }

            foreach (preg_split('/\s+/', trim($value)) ?: [] as $class) {
                if ($class !== '') {
                    $classes[$class] = true;
                }
            }
        }

        return array_keys($classes);
    }

    /** @param array<string,list<string>> $missing */
    private function format(array $missing): string
    {
        $lines = [];

        foreach ($missing as $template => $items) {
            $lines[] = $template . ': ' . implode(', ', array_unique($items));
        }

        return implode("\n", $lines);
    }
}
