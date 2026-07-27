<?php

namespace App\Support;

/**
 * Partner site şablon (template) kayıt defteri.
 *
 * Her template = resources/views/public/partner-templates/{key}.blade.php ve HEPSİ
 * aynı veri sözleşmesini (App\Support\PartnerSiteData::forDealer) kullanır — böylece
 * partner içeriğini bir kez girer, hangi template'i seçerse aynı veriyle dolar.
 *
 * Yeni template eklemek: (1) blade dosyasını partner-templates/'a koy,
 * (2) buraya bir satır ekle. Başka yeri değiştirmeye gerek yok.
 */
class PartnerTemplates
{
    public const DEFAULT = 'aurora';

    /** key => [name, desc, accent(default renk önerisi)] */
    public const TEMPLATES = [
        'aurora' => [
            'name'   => 'Aurora',
            'desc'   => 'Canlı gradient hero + cam efektli süreç paneli. Modern, sıcak, dinamik.',
            'accent' => '#0d9488',
        ],
        'minimal' => [
            'name'   => 'Minimal',
            'desc'   => 'Bol boşluk, ince çizgiler, sade tipografi. Şık ve zarif.',
            'accent' => '#4f46e5',
        ],
        'bold' => [
            'name'   => 'Bold',
            'desc'   => 'Koyu, iddialı hero + güçlü kontrast. Kurumsal ve etkileyici.',
            'accent' => '#e11d48',
        ],
    ];

    /** Geçerli template anahtarı mı? */
    public static function isValid(?string $key): bool
    {
        return is_string($key) && array_key_exists($key, self::TEMPLATES);
    }

    /** Geçerliyse key'i, değilse default'u döner. */
    public static function resolve(?string $key): string
    {
        return self::isValid($key) ? $key : self::DEFAULT;
    }

    /** Blade view adı: public.partner-templates.{key} */
    public static function view(?string $key): string
    {
        return 'public.partner-templates.' . self::resolve($key);
    }

    /** @return array<string,array{name:string,desc:string,accent:string}> */
    public static function all(): array
    {
        return self::TEMPLATES;
    }
}
