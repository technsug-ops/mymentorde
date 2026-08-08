<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Bugün dokunulan ekranlar GERÇEKTEN açılıyor mu?
 *
 * ── NEDEN BU TEST VAR ───────────────────────────────────────────────────
 * Blade'in derlenmesi sayfanın çalışması demek değil. Bugün iki kez tam da
 * bu yüzden 500 alındı:
 *   • `@php` bloğu içine Blade yorumu yazılmıştı (manager/requests)
 *   • direktif bitişik yazılmıştı: "etkin@if(...)" (Hesabım)
 * İkisi de yalnızca sayfa AÇILDIĞINDA patlıyor.
 *
 * Bu test, düzenlenen her ekranı gerçekten isteyip 500 dönmediğini
 * doğruluyor. İçeriği değil, ayakta olmayı ölçer.
 */
class TouchedPagesSmokeTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function owner(): User
    {
        return $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
    }

    private function manager(): User
    {
        return $this->userFor($this->companyA, User::ROLE_MANAGER);
    }

    /** Platform konsolu — mail taşıyıcısı, gönderen adresi, kullanıcı ekleme alanları. */
    public function test_platform_pages_render(): void
    {
        $owner = $this->owner();

        $pages = [
            '/platform/companies',
            '/platform/companies/' . $this->companyB->id,
            '/platform/settings',
            '/platform/form-template',
            '/platform/tenant-scope',
        ];

        foreach ($pages as $page) {
            $status = $this->actingAs($owner)->get($page)->getStatusCode();

            $this->assertLessThan(500, $status, "{$page} sunucu hatasi verdi ({$status}).");
        }
    }

    /** Üst firma ekranları — partner yönetimi ve danışman detayı. */
    public function test_manager_pages_render(): void
    {
        $manager = $this->manager();

        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        $advisor = $this->userFor($this->companyA, User::ROLE_SENIOR);

        $pages = [
            '/manager/partners',
            '/manager/partners/' . $this->companyB->id,
            '/manager/seniors/' . urlencode($advisor->email),
            '/manager/users',
            '/manager/account',
            '/manager/partner-agreements',
        ];

        foreach ($pages as $page) {
            $status = $this->actingAs($manager)->withSession(['2fa_passed' => true])
                ->get($page)->getStatusCode();

            $this->assertLessThan(500, $status, "{$page} sunucu hatasi verdi ({$status}).");
        }
    }

    /** Partner paneli — bugün eklenen ekranların tamamı. */
    public function test_partner_pages_render(): void
    {
        $this->companyB->update([
            'panel_mode'        => Company::PANEL_PARTNER,
            'parent_company_id' => $this->companyA->id,
        ]);
        Company::flushPanelModeCache();
        Company::flushHierarchyCache();

        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $pages = [
            '/manager/guests',
            '/manager/students',
            '/manager/process-info',
            '/manager/partner-documents',
            '/manager/partner-documents/requests',
            '/manager/partner-requests/incoming',
            '/manager/partner-agreements',
            '/manager/users',
            '/manager/account',
            '/im',
        ];

        foreach ($pages as $page) {
            $status = $this->actingAs($manager)->withSession(['2fa_passed' => true])
                ->get($page)->getStatusCode();

            $this->assertLessThan(500, $status, "{$page} sunucu hatasi verdi ({$status}).");
        }
    }

    /** Herkese açık demo sayfaları — girişsiz açılmalı. */
    public function test_public_demo_pages_render(): void
    {
        foreach (['/demo/bayi-sitesi', '/demo/bayi-sitesi/ozlem'] as $page) {
            $this->get($page)->assertOk();
        }
    }
}
