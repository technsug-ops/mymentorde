<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Support\PartnerSiteData;
use App\Support\PartnerTemplates;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
    /** Şablon seçilebilir: aday bayi hangi tasarımı istediğini görsün. */
    public function show(Request $request, ?string $template = null): View
    {
        // resolve() geçersiz anahtarı varsayılana düşürür — ziyaretçi adres
        // çubuğuna ne yazarsa yazsın sayfa açılır.
        $key = PartnerTemplates::resolve($template);

        $dealer = $this->sampleDealer($key);

        $data = PartnerSiteData::forDealer($dealer, null);

        return view(PartnerTemplates::view($key), $data + [
            'isPreview'    => true,
            'demoMode'     => true,
            'demoTemplate' => $key,
            'demoOptions'  => PartnerTemplates::all(),
        ]);
    }

    /**
     * Örnek bayi — KAYDEDİLMEZ.
     *
     * Gerçek bir bayinin doldurduğu alanların hepsi dolu, böylece aday bayi
     * "benim sitem de böyle olabilir" diyebilsin. İsim bilerek uydurma ve
     * "Demo" ibaresi taşıyor: gerçek bir firmayla karıştırılmamalı.
     */
    private function sampleDealer(string $template): Dealer
    {
        $dealer = new Dealer();

        $dealer->forceFill([
            'code'              => 'DEMO',
            'name'              => 'Demo Eğitim Danışmanlığı',
            'public_slug'       => 'demo',
            'site_enabled'      => true,
            'site_template'     => $template,
            'site_accent_color' => '#0f6bdc',

            'site_hero_title'    => 'Almanya\'da Üniversite Hayaliniz İçin Yanınızdayız',
            'site_hero_subtitle' => 'Başvurudan vizeye, dil kursundan yerleşime kadar tüm süreci '
                                    . 'sizin adınıza yürütüyoruz. İlk görüşme ücretsiz.',

            'site_about_text' => 'Demo Eğitim Danışmanlığı olarak 2015\'ten bu yana öğrencileri '
                                 . 'Almanya\'daki üniversitelerle buluşturuyoruz. Uni-Assist '
                                 . 'başvurusundan vize randevusuna kadar her adımda yanınızdayız.',

            'site_phone'     => '+90 555 000 00 00',
            'site_whatsapp'  => '+90 555 000 00 00',
            'site_instagram' => 'demo.egitim',
            'site_address'   => 'Örnek Mah. Demo Cad. No:1, İstanbul',

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

            'site_team' => [
                ['name' => 'A. Yılmaz', 'role' => 'Kurucu Danışman'],
                ['name' => 'B. Kaya',   'role' => 'Başvuru Uzmanı'],
                ['name' => 'C. Demir',  'role' => 'Vize Uzmanı'],
            ],

            'site_testimonials' => [
                ['name' => 'Elif S.', 'text' => 'Başvurudan vizeye kadar her adımda yanımdaydılar. TU Berlin\'e kabul aldım.'],
                ['name' => 'Murat T.', 'text' => 'Belgelerimi tek tek kontrol ettiler, hiçbir eksikle karşılaşmadım.'],
            ],

            'site_show_badge' => false,
        ]);

        return $dealer;
    }
}
