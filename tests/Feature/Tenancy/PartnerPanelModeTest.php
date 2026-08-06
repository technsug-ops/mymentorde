<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Partner panel modu — sade takip penceresi.
 *
 * SORUN: partner firmalar `manager` rolü aldığı için MentorDE'nin tam
 * panelini görüyordu — İnsan Kaynakları, Finans, VIP Oversight, Sistem
 * Yönetimi, AI Labs, UniMatch, bayi ağı... 60'tan fazla sayfa. Partnerlere
 * yazılım satmıyoruz; onlar öğrencilerini devredip süreci izliyorlar.
 *
 * ⚠ EN KRİTİK NOKTA: menüyü gizlemek YETMEZ. Adresi bilen yine girerdi.
 * Kısıt istek seviyesinde uygulanıyor (RestrictPartnerPanel).
 */
class PartnerPanelModeTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    private function partnerManager(): User
    {
        $this->companyB->update(['panel_mode' => Company::PANEL_PARTNER]);
        Company::flushPanelModeCache();

        return $this->userFor($this->companyB, User::ROLE_MANAGER);
    }

    // ── Adresler gerçekten kapanıyor mu ─────────────────────────────────────

    /**
     * ASIL KORUMA: yönetim adresleri kapalı. Menüyü gizlemek gösterişten
     * ibaret olurdu; bu test adresi doğrudan denemeyi ölçüyor.
     *
          */
    public function test_management_areas_are_closed_in_partner_mode(): void
    {
        $manager = $this->partnerManager();

        $blocked = [
            '/manager/hr',
            '/manager/finance',
            '/manager/system',
            '/manager/seniors',
            '/manager/staff',
            '/manager/revenue-analytics',
            '/manager/audit-log',
            '/manager/brand',
            '/manager/dealer-applications',
            '/manager/ai-assistant',
        ];

        foreach ($blocked as $path) {
            $response = $this->asStaff($manager)->get($path);

            $this->assertSame(
                404,
                $response->getStatusCode(),
                "Partner moduna ragmen {$path} acik kaldi."
            );
        }
    }

    /** İzin verilen alanlar çalışmaya devam etmeli. */
    public function test_allowed_areas_remain_open(): void
    {
        $manager = $this->partnerManager();

        foreach (['/manager/guests', '/manager/account', '/manager/leads/create'] as $path) {
            $response = $this->asStaff($manager)->get($path);

            $this->assertNotSame(
                404,
                $response->getStatusCode(),
                "Partnerin ihtiyaci olan {$path} kapatildi."
            );
        }
    }

    // ── Tam panel etkilenmemeli ─────────────────────────────────────────────

    /** Varsayılan `full`: mevcut şirketlerin davranışı DEĞİŞMEZ. */
    public function test_full_mode_company_is_not_restricted(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $response = $this->asStaff($manager)->get('/manager/hr');

        $this->assertNotSame(404, $response->getStatusCode(), 'Tam panel firmasi kisitlandi.');
    }

    public function test_panel_mode_defaults_to_full(): void
    {
        $this->assertFalse(Company::isPartnerPanel((int) $this->companyB->id));
    }

    // ── Menü ────────────────────────────────────────────────────────────────

    /**
     * Menüde kapalı alanlara BAĞLANTI olmamalı.
     *
     * Etiket metnine bakmak yanıltıcı: "Finans" gibi kelimeler dashboard
     * içeriğinde de geçebiliyor. Ölçülmesi gereken şey bağlantının kendisi —
     * kullanıcı tıklayacak bir kapı bulamamalı.
     */
    public function test_partner_menu_has_no_links_to_closed_areas(): void
    {
        $manager = $this->partnerManager();

        $html = $this->asStaff($manager)->get('/manager/guests')->getContent();

        $closed = [
            '/manager/hr',
            '/manager/finance',
            '/manager/system',
            '/manager/seniors',
            '/manager/staff',
            '/manager/audit-log',
            '/manager/brand',
            '/manager/ai-assistant',
            '/manager/revenue-analytics',
        ];

        foreach ($closed as $path) {
            $this->assertStringNotContainsString(
                'href="' . $path . '"',
                $html,
                "Partner menusunde {$path} baglantisi duruyor."
            );
        }
    }

    public function test_partner_menu_shows_the_essentials(): void
    {
        $manager = $this->partnerManager();

        $html = $this->asStaff($manager)->get('/manager/guests')->getContent();

        $expected = [
            'Aday Öğrenciler',   // aday listesi
            'Öğrenciler',        // öğrenci listesi
            'Aday Ekle',
            'Süreç Bilgisi',     // operasyonun yaptığı takip, salt okunur
            'Mesajlar',          // öğrenci ve atanan danışmanla iletişim
            'Destek Talepleri',
            'Belge Listesi',
            'Hesabım',
        ];

        foreach ($expected as $item) {
            $this->assertStringContainsString($item, $html, "Partner menusunde '{$item}' yok.");
        }
    }

    // ── Bağlam ──────────────────────────────────────────────────────────────

    /**
     * Karar ÇALIŞILAN şirkete göre verilir, kullanıcının kendi şirketine göre
     * değil: MentorDE personeli partnere geçtiğinde de o firmanın yüzeyini
     * görmeli, yoksa partnerin göremediği ekranda onun adına işlem yapardı.
     */
    public function test_switching_into_a_partner_applies_its_panel_mode(): void
    {
        $this->companyB->update([
            'parent_company_id' => $this->companyA->id,
            'panel_mode' => Company::PANEL_PARTNER,
        ]);
        Company::flushHierarchyCache();
        Company::flushPanelModeCache();

        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        // Kendi şirketinde tam panel
        $this->assertNotSame(404, $this->asStaff($manager)->get('/manager/hr')->getStatusCode());

        // Partnere geçince daralıyor
        $this->asStaff($manager)->post('/company-context/switch', ['company_id' => $this->companyB->id]);

        $this->assertSame(
            404,
            $this->asStaff($manager)->get('/manager/hr')->getStatusCode(),
            'Partner baglamina gecildi ama tam panel acik kaldi.'
        );
    }

    // ── Panel ───────────────────────────────────────────────────────────────

    public function test_owner_can_switch_a_company_to_partner_mode(): void
    {
        $owner = $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);

        $this->actingAs($owner)
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'panel_mode' => Company::PANEL_PARTNER,
            ])
            ->assertRedirect();

        $this->assertSame(Company::PANEL_PARTNER, $this->companyB->fresh()->panel_mode);
    }
}
