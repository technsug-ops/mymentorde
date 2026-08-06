<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Support\PartnerSiteData;
use App\Support\PartnerTemplates;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Bayi mini-sitesinin herkese açık ÖRNEĞİ — aday bayiye gösterilir.
 *
 * ── NEDEN VERİTABANINA SAHTE BAYİ EKLEMİYORUZ ───────────────────────────
 * Demo için gerçek bir Dealer kaydı açmak kolay olurdu ama o kayıt bayi
 * listelerine, komisyon hesaplarına, KPI'lara ve raporlara karışırdı.
 * Buradaki bayi kaydedilmez (`new Dealer` — save yok): aynı şablondan
 * geçer, hiçbir sorguda görünmez.
 *
 * ── NEDEN /demo ALTINDAKİ SAYFALAR AÇILMADI ─────────────────────────────
 * `/demo`, `/demo/guest` gerçek panellere kısayol veriyor (/manager/dashboard,
 * /config). Onlar demo değil, canlı ekranlar — dışarı açmak gerçek öğrenci
 * verisini ifşa ederdi. Bu yüzden ayrı ve veriden bağımsız bir sayfa.
 *
 * ⚠ Başvuru formuna bağlanmaz: demo siteden gelen bir kayıt hangi bayiye
 * yazılacaktı? CTA'lar tanıtım metnine döner (bkz. `demo` bayrağı).
 */
class DealerSiteDemoController extends Controller
{
    /**
     * İsimli demo profilleri.
     *
     * Aday bayiye kendi adıyla bir örnek göstermek satışta işe yarıyor.
     * Profil eklemek için buraya bir satır yeter.
     *
     * @var array<string,array{name:string,hero:string,about:string,city:string,logo?:string}>
     */
    private const PROFILES = [
        'ozlem' => [
            'name'  => 'Özlem Yurtdışı Danışmanlık',
            'hero'  => 'Almanya\'da Eğitim Yolculuğunuz Özlem Yurtdışı Danışmanlık ile Başlasın',
            'about' => 'Özlem Yurtdışı Danışmanlık olarak öğrencileri Almanya\'daki üniversitelerle '
                       . 'buluşturuyoruz. Uni-Assist başvurusundan vize randevusuna, dil kursundan '
                       . 'yerleşime kadar sürecin her adımında yanınızdayız.',
            'city'  => 'İstanbul',
            'logo'  => 'img/demo/ozlem-logo.svg',
        ],
    ];

    /**
     * Şablon ve profil aynı adresten seçilir.
     *
     * İki serbest parça var ve sıraları belirsiz olabilirdi. Kural basit:
     * ilk parça TANINAN BİR PROFİL ise profildir, değilse şablondur. Böylece
     * daha önce paylaşılmış /demo/bayi-sitesi/aurora gibi bağlantılar
     * çalışmaya devam ediyor.
     */
    public function show(Request $request, ?string $first = null, ?string $second = null): Response
    {
        $isProfile = $first !== null && isset(self::PROFILES[$first]);

        $profileKey = $isProfile ? $first : null;
        // resolve() geçersiz anahtarı varsayılana düşürür — ziyaretçi adres
        // çubuğuna ne yazarsa yazsın sayfa açılır.
        $key = PartnerTemplates::resolve($isProfile ? $second : $first);

        $dealer = $this->sampleDealer($key, $profileKey);

        $logoPath = self::PROFILES[$profileKey]['logo'] ?? null;

        $data = PartnerSiteData::forDealer($dealer, $logoPath ? asset($logoPath) : null);

        return response()
            ->view(PartnerTemplates::view($key), $data + [
                'isPreview'    => true,
                'demoMode'     => true,
                'demoTemplate' => $key,
                'demoProfile'  => $profileKey,
                'demoOptions'  => PartnerTemplates::all(),
            ])
            // ⚠ ARAMA MOTORLARINA KAPALI. İsimli demolar gerçek bir firmanın
            // adını taşıyor; dizine girerse o firmanın sitesi sanılabilir.
            // Bağlantı elden paylaşılır, aranarak bulunmaz.
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Örnek bayi — KAYDEDİLMEZ.
     *
     * Gerçek bir bayinin doldurduğu alanların hepsi dolu, böylece aday bayi
     * "benim sitem de böyle olabilir" diyebilsin. İsim bilerek uydurma ve
     * "Demo" ibaresi taşıyor: gerçek bir firmayla karıştırılmamalı.
     */
    private function sampleDealer(string $template, ?string $profileKey = null): Dealer
    {
        $profile = self::PROFILES[$profileKey] ?? null;

        $name = $profile['name'] ?? 'Demo Eğitim Danışmanlığı';

        $dealer = new Dealer();

        $dealer->forceFill([
            'code'              => 'DEMO',
            'name'              => $name,
            'public_slug'       => 'demo',
            'site_enabled'      => true,
            'site_template'     => $template,
            'site_accent_color' => '#0f6bdc',

            'site_hero_title'    => $profile['hero']
                ?? 'Almanya\'da Üniversite Hayaliniz İçin Yanınızdayız',
            'site_hero_subtitle' => 'Başvurudan vizeye, dil kursundan yerleşime kadar tüm süreci '
                                    . 'sizin adınıza yürütüyoruz. İlk görüşme ücretsiz.',

            'site_about_text' => $profile['about']
                ?? 'Demo Eğitim Danışmanlığı olarak 2015\'ten bu yana öğrencileri '
                   . 'Almanya\'daki üniversitelerle buluşturuyoruz. Uni-Assist '
                   . 'başvurusundan vize randevusuna kadar her adımda yanınızdayız.',

            // İletişim bilgileri örnek: gerçek numara/adres yazmak, demoyu
            // gerçek bir firma sitesi sanan ziyaretçiyi yanlış yere yönlendirir.
            'site_phone'     => '+90 555 000 00 00',
            'site_whatsapp'  => '+90 555 000 00 00',
            'site_instagram' => 'ornek.danismanlik',
            'site_address'   => 'Örnek Mah. Demo Cad. No:1, ' . ($profile['city'] ?? 'İstanbul'),

            'site_stats' => [
                ['value' => '850+', 'label' => 'Yerleştirilen Öğrenci'],
                ['value' => '40+',  'label' => 'Anlaşmalı Üniversite'],
                ['value' => '%94',  'label' => 'Vize Başarı Oranı'],
                ['value' => '9 yıl', 'label' => 'Tecrübe'],
            ],

            'site_services' => [
                ['title' => 'Üniversite Seçimi',   'text' => 'Notunuza ve bütçenize uygun bölüm ve şehir önerisi.'],
                ['title' => 'Uni-Assist Başvurusu', 'text' => 'Belge denkliği, çeviri ve başvuru dosyasının hazırlanması.'],
                ['title' => 'Dil Kursu',            'text' => 'Almanya\'da veya Türkiye\'de kurs yerleştirme.'],
                ['title' => 'Vize Süreci',          'text' => 'Randevu, evrak listesi ve mülakat hazırlığı.'],
                ['title' => 'Konaklama',            'text' => 'Yurt ve ev bulma desteği, kira sözleşmesi kontrolü.'],
                ['title' => 'Yerleşim',             'text' => 'İkamet kaydı, banka hesabı, sigorta işlemleri.'],
            ],

            // ⚠ SAYILAR IZGARAYA GÖRE. Ekip 4'lü, yorumlar 3'lü ızgarada
            // basılıyor; eksik kalan hücre boşluk bırakıp sayfayı sola yaslıyor.
            // Demo tam satır göstermeli, aday bayi tasarımı bozuk sanmasın.
            'site_team' => [
                ['name' => 'A. Yılmaz', 'role' => 'Kurucu Danışman'],
                ['name' => 'B. Kaya',   'role' => 'Başvuru Uzmanı'],
                ['name' => 'C. Demir',  'role' => 'Vize Uzmanı'],
                ['name' => 'D. Şahin',  'role' => 'Yerleşim Danışmanı'],
            ],

            'site_testimonials' => [
                ['name' => 'Elif S.',  'school' => 'TU Berlin', 'text' => 'Başvurudan vizeye kadar her adımda yanımdaydılar. TU Berlin\'e kabul aldım.'],
                ['name' => 'Murat T.', 'school' => 'RWTH Aachen', 'text' => 'Belgelerimi tek tek kontrol ettiler, hiçbir eksikle karşılaşmadım.'],
                ['name' => 'Zeynep A.', 'school' => 'Uni Köln', 'text' => 'Vize randevusuna hazırlıklı gittim; sorulacak soruları önceden çalıştık.'],
            ],

            'site_show_badge' => false,
        ]);

        return $dealer;
    }
}
