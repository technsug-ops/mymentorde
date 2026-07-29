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

    /**
     * key => [name, desc, accent(default renk önerisi), modular, sections]
     *
     *  modular  true  → bölümleri partial'lara ayrılmış: partnerin SIRA ve AÇ/KAPA seçimi geçerli
     *           false → sabit kurgulu tek dosya (eski şablonlar); sıra/aç-kapa yok sayılır
     *  sections       modular şablonun bastığı bölüm anahtarları (PartnerSiteSections::SECTIONS)
     *
     * Editör bu bilgiyle "bu şablonda yok / sıra bu şablonda geçerli değil" uyarısını gösterir.
     */
    public const TEMPLATES = [
        'aurora' => [
            'name'     => 'Aurora',
            'desc'     => 'Canlı gradient hero + cam efektli süreç paneli. Modern, sıcak, dinamik.',
            'accent'   => '#0d9488',
            'modular'  => false,
            'sections' => ['services', 'steps', 'stats', 'about', 'testimonials', 'why'],
        ],
        'minimal' => [
            'name'     => 'Minimal',
            'desc'     => 'Bol boşluk, ince çizgiler, sade tipografi. Şık ve zarif.',
            'accent'   => '#4f46e5',
            'modular'  => false,
            'sections' => ['services', 'steps', 'stats', 'about', 'testimonials', 'why'],
        ],
        'bold' => [
            'name'     => 'Bold',
            'desc'     => 'Koyu, iddialı hero + güçlü kontrast. Kurumsal ve etkileyici.',
            'accent'   => '#e11d48',
            'modular'  => false,
            'sections' => ['services', 'steps', 'stats', 'about', 'testimonials', 'why'],
        ],
        'lavanta' => [
            'name'     => 'Lavanta',
            'desc'     => 'Yuvarlak ve samimi; pastel zemin, yumuşak kartlar. Bölümleri sıralanabilir.',
            'accent'   => '#8b7fd6',
            'modular'  => true,
            'sections' => ['unis', 'services', 'steps', 'stats', 'about', 'testimonials', 'why', 'packages', 'faq'],
        ],
        'seftali' => [
            'name'     => 'Şeftali Sabahı',
            'desc'     => 'Editoryal serif, sıcak şeftali tonları; dergi hissi, sola dayalı başlıklar.',
            'accent'   => '#e8846a',
            'modular'  => true,
            'sections' => ['stats', 'unis', 'services', 'steps', 'about', 'testimonials', 'why', 'packages', 'faq'],
        ],
        'nane' => [
            'name'     => 'Nane',
            'desc'     => 'Minimal ve ferah: ortalanmış hero, gölgesiz ince çizgiler, bol boşluk.',
            'accent'   => '#57b98a',
            'modular'  => true,
            'sections' => ['unis', 'services', 'steps', 'stats', 'about', 'testimonials', 'why', 'packages', 'faq'],
        ],
    ];

    /** Şablon bölümleri sıralanabilir/kapatılabilir mi? */
    public static function isModular(?string $key): bool
    {
        return (bool) (self::TEMPLATES[self::resolve($key)]['modular'] ?? false);
    }

    /**
     * Şablonun bastığı bölüm anahtarları (bilinmiyorsa tümü sayılır).
     *
     * @return list<string>
     */
    public static function sectionsOf(?string $key): array
    {
        $s = self::TEMPLATES[self::resolve($key)]['sections'] ?? null;

        return is_array($s) ? array_values($s) : PartnerSiteSections::defaultOrder();
    }

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
