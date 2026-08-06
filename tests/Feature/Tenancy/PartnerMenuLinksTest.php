<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Partner menüsündeki HER bağlantı gerçekten çalışmalı.
 *
 * ── NEDEN BU TEST VAR ───────────────────────────────────────────────────
 * Partner menüsü kurulurken hedefler manager layout'undaki href listesinden
 * seçilmişti ama TEK TEK DENENMEMİŞTİ. Sonuç: canlıda iki kırık bağlantı —
 * /manager/document-requests 404 (öyle bir rota yok, yalnızca /analytics ve
 * /bulk alt yolları var), /manager/requests 500.
 *
 * Menüye bir madde eklemek, o sayfanın PARTNER BAĞLAMINDA açıldığını
 * doğrulamayı gerektirir. Bu test onu otomatikleştirir: menüdeki her
 * bağlantı gezilir ve hata dönen varsa isim isim raporlanır.
 */
class PartnerMenuLinksTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function partnerManager(): User
    {
        $this->companyB->update(['panel_mode' => Company::PANEL_PARTNER]);
        Company::flushPanelModeCache();

        return $this->userFor($this->companyB, User::ROLE_MANAGER);
    }

    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    /**
     * Menüdeki bağlantıları HTML'den çıkar — elle liste tutmak, menü
     * değiştiğinde testin sessizce eskimesi demek olurdu.
     *
     * @return list<string>
     */
    private function menuLinks(User $manager): array
    {
        $html = $this->asStaff($manager)->get('/manager/guests')->getContent();

        preg_match_all('#href="(/[a-z0-9\-/]+)"#i', $html, $matches);

        $links = array_values(array_unique($matches[1] ?? []));

        // Çıkış menüde ama oturumu kapatır — gezme.
        return array_values(array_filter(
            $links,
            static fn (string $l): bool => $l !== '/logout'
        ));
    }

    public function test_every_partner_menu_link_works(): void
    {
        $manager = $this->partnerManager();
        $links = $this->menuLinks($manager);

        $this->assertNotEmpty($links, 'Partner menusunde hic baglanti bulunamadi.');

        $broken = [];

        foreach ($links as $link) {
            $status = $this->asStaff($manager)->get($link)->getStatusCode();

            // 200 ve yönlendirmeler kabul; 4xx/5xx kirik.
            if ($status >= 400) {
                $broken[$link] = $status;
            }
        }

        $this->assertSame(
            [],
            $broken,
            "Partner menusunde kirik baglanti(lar): " . json_encode($broken, JSON_UNESCAPED_SLASHES)
        );
    }
}
