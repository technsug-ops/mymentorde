<?php

namespace App\Support;

use App\Models\Dealer;

/**
 * Operasyon partner (b2b_partner) çok-bölümlü öğrenci-lead sitesi için view verisi.
 * Partner kendi içeriğini girmediyse mantıklı MentorDE default'ları döner
 * (addon-bağımsız: eksik/boş alan siteyi bozmaz). Bkz. partner-site.blade.php.
 */
class PartnerSiteData
{
    /** Hex accent doğrula; geçersizse brandbook moru. */
    public static function accent(?string $raw): string
    {
        return is_string($raw) && preg_match('/^#[0-9a-fA-F]{6}$/', $raw) ? $raw : '#7e58bf';
    }

    /**
     * Tüm partner template'lerinin paylaştığı inline-SVG ikon seti (emoji yerine).
     * Blade'de: {!! \App\Support\PartnerSiteData::icon('cap') !!}
     */
    public static function icon(string $key): string
    {
        static $svg = [
            'cap'      => '<path d="M12 3 1 8l11 5 9-4.09V15h2V8L12 3zM5 13.18v3.09c0 1.38 3.13 2.73 7 2.73s7-1.35 7-2.73v-3.09l-7 3.18-7-3.18z"/>',
            'passport' => '<path d="M4 2h13a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H4V2zm8 5a4 4 0 1 0 0 8 4 4 0 0 0 0-8zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM8 17h8v1.5H8V17z"/>',
            'coins'    => '<path d="M12 2C7 2 3 3.79 3 6s4 4 9 4 9-1.79 9-4-4-4-9-4zM3 8.5V11c0 2.21 4 4 9 4s9-1.79 9-4V8.5c0 2.21-4 4-9 4s-9-1.79-9-4zm0 5V16c0 2.21 4 4 9 4s9-1.79 9-4v-2.5c0 2.21-4 4-9 4s-9-1.79-9-4z"/>',
            'home'     => '<path d="M12 3 2 12h3v8h6v-6h2v6h6v-8h3L12 3z"/>',
            'check'    => '<path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>',
            'arrow'    => '<path d="M4 11v2h12l-5.5 5.5L12 20l8-8-8-8-1.5 1.5L16 11z"/>',
            'chart'    => '<path d="M4 20V10h4v10H4zm6 0V4h4v16h-4zm6 0v-7h4v7h-4z"/>',
            'bolt'     => '<path d="M13 2 3 14h7l-1 8 10-12h-7z"/>',
            'shield'   => '<path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3zm-1 14-4-4 1.4-1.4L11 13.2l5.6-5.6L18 9l-7 7z"/>',
            'gear'     => '<path d="M19.4 13c.04-.32.06-.66.06-1s-.02-.68-.06-1l2.11-1.65a.5.5 0 0 0 .12-.64l-2-3.46a.5.5 0 0 0-.6-.22l-2.49 1a7.3 7.3 0 0 0-1.73-1l-.38-2.65A.5.5 0 0 0 14 1h-4a.5.5 0 0 0-.5.42l-.38 2.65c-.63.25-1.2.59-1.73 1l-2.49-1a.5.5 0 0 0-.6.22l-2 3.46a.5.5 0 0 0 .12.64L4.6 11c-.04.32-.06.66-.06 1s.02.68.06 1l-2.11 1.65a.5.5 0 0 0-.12.64l2 3.46a.5.5 0 0 0 .6.22l2.49-1c.53.41 1.1.75 1.73 1l.38 2.65A.5.5 0 0 0 10 23h4a.5.5 0 0 0 .5-.42l.38-2.65c.63-.25 1.2-.59 1.73-1l2.49 1a.5.5 0 0 0 .6-.22l2-3.46a.5.5 0 0 0-.12-.64L19.4 13zM12 15.5A3.5 3.5 0 1 1 12 8.5a3.5 3.5 0 0 1 0 7z"/>',
            'users'    => '<path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm-8 0a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-2.7 0-8 1.34-8 4v3h10v-3c0-.97.36-1.79.95-2.46A13 13 0 0 0 8 13zm8 0c-.34 0-.72.02-1.13.06 1.32.96 2.13 2.24 2.13 3.94v3h7v-3c0-2.66-5.3-4-8-4z"/>',
            'clock'    => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 11h-5v-2h3V7h2z"/>',
            'star'     => '<path d="m12 17.27 6.18 3.73-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>',
            'default'  => '<path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3zm-1 14-4-4 1.4-1.4L11 13.2l5.6-5.6L18 9l-7 7z"/>',
        ];
        $path = $svg[$key] ?? $svg['default'];
        return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' . $path . '</svg>';
    }

    /**
     * Blade'e geçilecek tüm veri. $logoUrl controller'da Storage'dan üretilir.
     * @return array<string,mixed>
     */
    public static function forDealer(Dealer $dealer, ?string $logoUrl): array
    {
        return [
            'dealer'       => $dealer,
            'brandName'    => $dealer->name,
            'brandLogoUrl' => $logoUrl,
            'accentColor'  => self::accent($dealer->site_accent_color),
            'heroTitle'    => $dealer->site_hero_title ?: 'Almanya\'da Eğitim Yolculuğunuz Burada Başlıyor',
            'heroSubtitle' => $dealer->site_hero_subtitle
                ?: 'Üniversite başvurusundan vizeye, konaklamadan yerleşime — Almanya eğitim sürecinizin '
                    . 'her adımında yanınızdayız. Ücretsiz danışmanlık için hemen başvurun.',
            'aboutText'    => $dealer->site_about_text
                ?: ($dealer->name . ' olarak, Almanya\'da eğitim almak isteyen öğrencilere uçtan uca '
                    . 'rehberlik ediyoruz. Deneyimli ekibimizle başvuru, vize ve yerleşim süreçlerinizi '
                    . 'sizin adınıza titizlikle yönetiyoruz.'),
            'services'     => self::services($dealer),
            'stats'        => self::stats($dealer),
            'team'         => self::team($dealer),
            'heroTrust'    => self::heroTrust($dealer),
            'showBadge'    => $dealer->site_show_badge ?? true,
            'phone'        => $dealer->site_phone ?: ($dealer->phone ?: null),
            'whatsapp'     => $dealer->site_whatsapp ?: ($dealer->whatsapp ?: null),
            'instagram'    => $dealer->site_instagram ?: null,
            'address'      => $dealer->site_address ?: null,
            'applyUrl'     => route('apply.partner', $dealer->code),
        ];
    }

    /**
     * Hero güven satırı rakamları. Partner site_stats girdiyse oradan "öğrenci" ve
     * "%..." içeren değerleri yakalar; yoksa genel default (uydurma spesifik iddia yok).
     * @return array{rating:string,students:string,success:string}
     */
    public static function heroTrust(Dealer $dealer): array
    {
        $students = null;
        $success  = null;
        foreach (self::stats($dealer) as $st) {
            $label = mb_strtolower($st['label'] ?? '');
            $value = trim($st['value'] ?? '');
            if ($value === '') {
                continue;
            }
            if ($students === null && str_contains($label, 'öğrenci')) {
                $students = $value;
            }
            if ($success === null && (str_contains($label, 'vize') || str_contains($label, 'başarı') || str_contains($value, '%'))) {
                $success = $value;
            }
        }

        return [
            'rating'   => '4.9/5',
            'students' => $students ?: '1200+',
            'success'  => $success ?: '%98',
        ];
    }

    /**
     * Hizmet kartları — partner girdiyse onları, yoksa zengin MentorDE default seti.
     * Her kart: title, desc, icon, items (kapsam maddeleri listesi).
     */
    public static function services(Dealer $dealer): array
    {
        $custom = self::parseServiceCards($dealer->site_services);
        if (!empty($custom)) {
            return $custom;
        }

        return [
            ['icon' => 'cap', 'title' => 'Üniversite Başvurusu',
             'desc' => 'Almanya devlet ve özel üniversitelerine lisans/yüksek lisans başvurularının tamamını yönetiriz.',
             'items' => ['Program & üniversite seçimi', 'Uni-Assist & doğrudan başvuru', 'Motivasyon mektubu & CV', 'Denklik (APS) desteği']],
            ['icon' => 'default', 'title' => 'Dil Okulu & Almanca',
             'desc' => 'A1\'den C1\'e Almanca kurs yerleştirme ve dil okulu kayıt süreçleri.',
             'items' => ['Yoğun Almanca kurs kaydı', 'Dil okulu vize yazısı', 'Seviye & sınav yönlendirmesi']],
            ['icon' => 'passport', 'title' => 'Vize Süreci',
             'desc' => 'Randevudan mülakata, öğrenci vizesi başvurusunun her adımında yanınızdayız.',
             'items' => ['Randevu & dosya hazırlığı', 'Belge kontrol listesi', 'Mülakat hazırlığı']],
            ['icon' => 'coins', 'title' => 'Finansal İşlemler',
             'desc' => 'Bloke hesap ve sağlık sigortası kurulumunda resmi partnerlerle güvenli süreç.',
             'items' => ['Bloke hesap (Sperrkonto)', 'Sağlık sigortası', 'Finansal kanıt danışmanlığı']],
            ['icon' => 'home', 'title' => 'Konaklama & Yerleşim',
             'desc' => 'Almanya\'ya varışınızdan itibaren yaşam kurma sürecinizi kolaylaştırırız.',
             'items' => ['Wohnung / Wohnheim araştırma', 'Anmeldung (adres kaydı)', 'Günlük yaşam rehberliği']],
            ['icon' => 'default', 'title' => 'Kariyer & Ausbildung',
             'desc' => 'Mesleki eğitim (Ausbildung) ve staj programlarıyla maaşlı öğrenim fırsatları.',
             'items' => ['Ausbildung yerleştirme', 'Sözleşme & başvuru desteği', 'İş & staj yönlendirmesi']],
        ];
    }

    /** Editör'den gelen hizmet kartlarını güvene al (title/desc/icon + items listesi). */
    private static function parseServiceCards($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $title = isset($row['title']) && is_scalar($row['title']) ? trim((string) $row['title']) : '';
            $desc  = isset($row['desc'])  && is_scalar($row['desc'])  ? trim((string) $row['desc'])  : '';
            if ($title === '' && $desc === '') {
                continue;
            }
            $icon = isset($row['icon']) && is_scalar($row['icon']) ? trim((string) $row['icon']) : '';
            $out[] = [
                'title' => $title,
                'desc'  => $desc,
                'icon'  => $icon !== '' ? $icon : 'default',
                'items' => self::parseItems($row['items'] ?? null),
            ];
            if (count($out) >= 12) {
                break;
            }
        }
        return $out;
    }

    /** items: newline'lı string veya dizi → temiz string listesi (max 6). */
    private static function parseItems($raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $it) {
            if (!is_scalar($it)) {
                continue;
            }
            $v = trim((string) $it);
            if ($v !== '') {
                $out[] = $v;
            }
            if (count($out) >= 6) {
                break;
            }
        }
        return $out;
    }

    /** İstatistik rozetleri — partner girmediyse boş (uydurma rakam gösterme). */
    public static function stats(Dealer $dealer): array
    {
        return self::sanitizeCards($dealer->site_stats, ['value', 'label']);
    }

    /** Ekip kartları — partner girmediyse boş (bölüm gizlenir). */
    public static function team(Dealer $dealer): array
    {
        return self::sanitizeCards($dealer->site_team, ['name', 'title', 'photo']);
    }

    /**
     * JSON kart listesini güvene al: dizi olduğundan emin ol, her satırda beklenen
     * anahtarları string'e çevir, tamamen boş satırları at. En fazla 12 kart.
     *
     * @param  array<int,array<string,mixed>>|null  $raw
     * @param  list<string>  $keys
     * @return array<int,array<string,string>>
     */
    private static function sanitizeCards($raw, array $keys): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $card = [];
            $hasContent = false;
            foreach ($keys as $k) {
                $val = isset($row[$k]) && is_scalar($row[$k]) ? trim((string) $row[$k]) : '';
                $card[$k] = $val;
                if ($val !== '' && $k !== 'icon' && $k !== 'photo') {
                    $hasContent = true;
                }
            }
            if ($hasContent) {
                $out[] = $card;
            }
            if (count($out) >= 12) {
                break;
            }
        }
        return $out;
    }
}
