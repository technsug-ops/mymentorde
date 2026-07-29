<?php

namespace App\Support;

/**
 * Partner sitesinin bölüm (modül) kurgusu: sıra + aç/kapa.
 *
 * Partner `/dealer/mini-site` editöründen bölümleri sıralar ve istemediğini kapatır;
 * seçim `dealers.site_sections` JSON alanında saklanır. Şablonlar bu sırayla partial
 * include eder: resources/views/public/partner-templates/{tpl}/sections/{key}.blade.php
 *
 * Sabit (sıralanamaz) parçalar: nav, hero, başvuru/iletişim, footer — her şablonun
 * iskeleti. Sıralanabilir bölümler aşağıdaki listedir.
 *
 * İLERİYE DÖNÜK UYUMLU: kayıtta olmayan yeni bölümler varsayılan sırasıyla sona eklenir
 * ve açık gelir; kayıtta duran bilinmeyen key sessizce düşer (şablon değişince bozulmaz).
 */
class PartnerSiteSections
{
    /**
     * key => [label, hint]
     * Sıra = varsayılan sıra (partner hiç düzenlemediyse bu kullanılır).
     *
     * @var array<string,array{label:string,hint:string}>
     */
    public const SECTIONS = [
        'unis'         => ['label' => 'Üniversite şeridi',   'hint' => 'Öğrencilerin yerleştiği üniversiteler'],
        'services'     => ['label' => 'Hizmetler',           'hint' => 'Hizmet kartları'],
        'steps'        => ['label' => 'Süreç',               'hint' => 'Dört adımda Almanya'],
        'stats'        => ['label' => 'İstatistik bandı',    'hint' => 'Girdiğiniz sayılar'],
        'about'        => ['label' => 'Hakkımızda',          'hint' => 'Tanıtım metni'],
        'testimonials' => ['label' => 'Öğrenci yorumları',   'hint' => 'Girdiğiniz gerçek yorumlar'],
        'why'          => ['label' => 'Neden biz + ekip',    'hint' => 'Çalışma biçimi, ekip kartları, güven rozeti'],
        'packages'     => ['label' => 'Destek paketleri',    'hint' => 'Paket kartları'],
        'faq'          => ['label' => 'S.S.S.',              'hint' => 'Sıkça sorulan sorular'],
    ];

    /** Geçerli bölüm anahtarı mı? */
    public static function isValid(?string $key): bool
    {
        return is_string($key) && array_key_exists($key, self::SECTIONS);
    }

    /** @return list<string> */
    public static function defaultOrder(): array
    {
        return array_keys(self::SECTIONS);
    }

    /**
     * Kayıtlı seçimi normalize et: bilinmeyen/yinelenen key düşer, eksik bölümler
     * varsayılan sırayla sona eklenir (açık).
     *
     * @param  mixed  $raw  dealers.site_sections
     * @return list<array{key:string,on:bool,label:string,hint:string}>
     */
    public static function resolve($raw): array
    {
        $out  = [];
        $seen = [];

        if (is_array($raw)) {
            foreach ($raw as $row) {
                // Hem [{key,on}] hem de düz ["services","faq"] biçimini kabul et.
                if (is_string($row)) {
                    $row = ['key' => $row, 'on' => true];
                }
                if (!is_array($row)) {
                    continue;
                }
                $key = isset($row['key']) && is_scalar($row['key']) ? (string) $row['key'] : '';
                if (!self::isValid($key) || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $out[] = self::row($key, filter_var($row['on'] ?? true, FILTER_VALIDATE_BOOLEAN));
            }
        }

        foreach (self::defaultOrder() as $key) {
            if (!isset($seen[$key])) {
                $out[] = self::row($key, true);
            }
        }

        return $out;
    }

    /**
     * Şablonun basacağı bölüm anahtarları — sırayla, sadece AÇIK olanlar.
     *
     * @return list<string>
     */
    public static function enabledKeys($raw): array
    {
        return array_values(array_map(
            static fn (array $r): string => $r['key'],
            array_filter(self::resolve($raw), static fn (array $r): bool => $r['on'])
        ));
    }

    /** @return array{key:string,on:bool,label:string,hint:string} */
    private static function row(string $key, bool $on): array
    {
        return [
            'key'   => $key,
            'on'    => $on,
            'label' => self::SECTIONS[$key]['label'],
            'hint'  => self::SECTIONS[$key]['hint'],
        ];
    }
}
