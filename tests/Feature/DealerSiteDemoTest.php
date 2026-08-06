<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Support\PartnerTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bayi mini-sitesinin herkese açık örneği.
 *
 * Amaç: aday bayiye "senin siten böyle görünecek" diyebilmek. Demek ki
 * bağlantı GİRİŞSİZ açılmalı — testin asıl ölçtüğü şey bu.
 *
 * ⚠ Mevcut /demo sayfaları bu işe yaramıyordu: onlar gerçek panellere
 * (/manager/dashboard, /config) kısayol veriyor, yani canlı veri. Bu sayfa
 * onlardan bağımsız ve veritabanına hiç dokunmuyor.
 */
class DealerSiteDemoTest extends TestCase
{
    use RefreshDatabase;

    /** ASIL GARANTİ: giriş yapmadan açılır. */
    public function test_demo_is_reachable_without_logging_in(): void
    {
        $this->get('/demo/bayi-sitesi')
            ->assertOk()
            ->assertSee('Demo Eğitim Danışmanlığı', false);
    }

    /**
     * Her şablon açılmalı.
     *
     * Şablon listesi koddan okunuyor; yeni tasarım eklendiğinde test onu
     * kendiliğinden kapsar ve kırık şablonu isim isim raporlar.
     */
    public function test_every_template_renders(): void
    {
        $broken = [];

        foreach (array_keys(PartnerTemplates::all()) as $key) {
            $status = $this->get('/demo/bayi-sitesi/' . $key)->getStatusCode();

            if ($status !== 200) {
                $broken[$key] = $status;
            }
        }

        $this->assertSame([], $broken, 'Acilmayan sablon(lar): ' . json_encode($broken));
    }

    /** Geçersiz şablon adı 404 değil, varsayılan tasarım vermeli. */
    public function test_unknown_template_falls_back(): void
    {
        $this->get('/demo/bayi-sitesi/olmayan-sablon')->assertOk();
    }

    /** İsimli demo profili kendi adıyla açılmalı. */
    public function test_named_profile_uses_its_own_name(): void
    {
        $this->get('/demo/bayi-sitesi/ozlem')
            ->assertOk()
            ->assertSee('Özlem Yurtdışı Danışmanlık', false);
    }

    /** Profil ve şablon aynı adreste birlikte seçilebilmeli. */
    public function test_named_profile_accepts_a_template(): void
    {
        $this->get('/demo/bayi-sitesi/ozlem/minimal')
            ->assertOk()
            ->assertSee('Özlem Yurtdışı Danışmanlık', false);
    }

    /**
     * GERİYE UYUM: profil eklenmeden önce paylaşılmış şablon bağlantıları
     * çalışmaya devam etmeli. İlk parça artık profil de olabildiği için bu
     * ayrım sessizce bozulabilirdi.
     */
    public function test_plain_template_links_still_work(): void
    {
        $this->get('/demo/bayi-sitesi/minimal')
            ->assertOk()
            ->assertSee('Demo Eğitim Danışmanlığı', false);
    }

    /**
     * Arama motorlarına kapalı olmalı.
     *
     * İsimli demo gerçek bir firmanın adını taşıyor; dizine girerse o
     * firmanın kendi sitesi sanılabilir. Bağlantı elden paylaşılır.
     */
    public function test_demo_is_not_indexable(): void
    {
        $this->get('/demo/bayi-sitesi/ozlem')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Demo veritabanına HİÇ yazmamalı.
     *
     * Sahte bir bayi kaydı açmak kolay olurdu ama o kayıt bayi listelerine,
     * komisyon hesaplarına ve raporlara karışırdı.
     */
    public function test_demo_creates_no_dealer_record(): void
    {
        $before = Dealer::withoutGlobalScope('company')->count();

        $this->get('/demo/bayi-sitesi')->assertOk();

        $this->assertSame(
            $before,
            Dealer::withoutGlobalScope('company')->count(),
            'Demo veritabanina bayi yazdi.'
        );
    }
}
