<?php

namespace App\Support;

/**
 * 250 ülke ISO 3166-1 alpha-2 katalogu.
 *
 * Label'lar runtime'da ext-intl (Locale::getDisplayRegion) ile dinamik
 * üretilir — TR/DE/EN dillerinde resmi UN/ISO isimleri. Composer paketi
 * gerekmez, vendor klasöründe statik veri yok.
 *
 * Sakla: ISO kodu (örn: 'TR'). Göster: locale label ('Türkiye'/'Türkei'/'Turkey').
 *
 * NOT: Eski ApplicationCountryCatalog sadece 12 hedef ülke (vize başvuru
 * yapılan ülkeler) için kullanılıyor — onu silmedik, geriye uyumlu.
 */
class CountryCatalog
{
    /** Geçerli locale'ler */
    public const SUPPORTED_LOCALES = ['tr', 'de', 'en'];

    /** Cache anahtarı (in-memory) */
    private static array $cache = [];

    /**
     * Belirtilen locale için tüm ülkeleri label sıralı array olarak döndürür.
     * Priority listesi (TR/DE/AT vb.) en başa konulur.
     *
     * @return array<int, array{code:string,label:string}>
     */
    public static function optionsForLocale(string $locale = 'tr'): array
    {
        $locale = in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'tr';

        if (isset(self::$cache[$locale])) {
            return self::$cache[$locale];
        }

        $cfg = (array) config('countries_iso', []);
        $priority = (array) ($cfg['priority'] ?? []);
        $all = (array) ($cfg['all'] ?? []);

        // Priority dışındakiler
        $rest = array_values(array_diff($all, $priority));

        // Label üretip alfabetik sırala
        $restWithLabels = array_map(
            static fn (string $code) => ['code' => $code, 'label' => self::labelByCode($code, $locale)],
            $rest
        );
        usort(
            $restWithLabels,
            static fn (array $a, array $b) => strnatcasecmp($a['label'], $b['label'])
        );

        // Priority başa
        $priorityWithLabels = array_map(
            static fn (string $code) => ['code' => $code, 'label' => self::labelByCode($code, $locale)],
            $priority
        );

        return self::$cache[$locale] = array_merge($priorityWithLabels, $restWithLabels);
    }

    /**
     * ISO kodundan label üretir. Bilinmeyen kod → kodun kendisi döner.
     */
    public static function labelByCode(string $code, string $locale = 'tr'): string
    {
        $code = strtoupper(trim($code));
        if ($code === '') return '';

        $locale = in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'tr';

        // ext-intl yoksa fallback — ISO kodu kendisi
        if (! extension_loaded('intl')) {
            return $code;
        }

        // "-XX" formatı: bölgeyi locale parser tarafından doğru tanımak için
        $label = \Locale::getDisplayRegion('-' . $code, $locale);

        // Locale unknown → kod döner (örn: XK için bazı versiyonlarda)
        if ($label === '' || $label === $code) {
            // Custom override — Kosova bazı PHP versiyonlarında boş döner
            $custom = self::customOverride($code, $locale);
            if ($custom !== null) return $custom;
            return $code;
        }

        return $label;
    }

    /**
     * Almanca uyruk karşılığı (örneğin "Türk" → "Türkei").
     * Vize VIDEX form'unda Staatsangehörigkeit alanı için.
     *
     * Mantık: önce ISO koduysa direkt label döner. Değilse Türkçe label
     * sözlüğünde aranır (örn. 'Türk' → 'TR' → 'Türkei').
     */
    public static function toGermanLabel(?string $value): ?string
    {
        if ($value === null || trim($value) === '') return null;
        $val = trim($value);

        // ISO kodu mu?
        if (preg_match('/^[A-Za-z]{2}$/', $val)) {
            return self::labelByCode($val, 'de');
        }

        // Türkçe label tersine arama
        $code = self::codeByLabel($val, 'tr');
        if ($code !== null) return self::labelByCode($code, 'de');

        // Tipik TR uyruk eşleşmeleri
        $low = mb_strtolower($val);
        $map = [
            'türk'    => 'Türkei',
            'turk'    => 'Türkei',
            'turkish' => 'Türkei',
            'alman'   => 'Deutschland',
            'german'  => 'Deutschland',
            'suriye'  => 'Syrien',
            'syrian'  => 'Syrien',
            'iran'    => 'Iran',
        ];
        if (isset($map[$low])) return $map[$low];

        return $val; // Bilinmeyen → girilen değeri olduğu gibi
    }

    /** Label'dan ISO kod ters arama (alfabetik linear scan, ~250 öğe). */
    public static function codeByLabel(string $label, string $locale = 'tr'): ?string
    {
        $label = trim($label);
        if ($label === '') return null;
        $low = mb_strtolower($label);

        foreach (self::optionsForLocale($locale) as $opt) {
            if (mb_strtolower($opt['label']) === $low) return $opt['code'];
        }
        return null;
    }

    /**
     * Belirli bazı ülkeler için manuel override (ext-intl boş veya
     * tutarsız döndürürse).
     */
    private static function customOverride(string $code, string $locale): ?string
    {
        $overrides = [
            'XK' => ['tr' => 'Kosova', 'de' => 'Kosovo', 'en' => 'Kosovo'],
        ];
        return $overrides[$code][$locale] ?? null;
    }

    /** Cache temizleme (test için). */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
