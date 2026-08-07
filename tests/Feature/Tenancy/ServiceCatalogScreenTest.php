<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\CompanyServiceExtra;
use App\Models\CompanyServicePackage;
use App\Models\GuestApplication;
use App\Models\User;
use App\Support\ServiceCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * /manager/services — firmanın kendi paket ve fiyatlarını yönettiği ekran.
 *
 * Buradaki asıl risk silme: bir paketi seçmiş adaylar varken satır silinirse
 * o kayıtların tutarı sessizce sıfıra düşer (kayıtta yalnızca KOD duruyor).
 */
class ServiceCatalogScreenTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function managerOf(Company $company): User
    {
        return $this->userFor($company, User::ROLE_MANAGER);
    }

    /** Personel ekranları 2FA arkasında — oturumu geçmiş say. */
    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    private function guestWithPackage(Company $company, string $code): GuestApplication
    {
        return TenantContext::runFor((int) $company->id, fn (): GuestApplication => GuestApplication::create([
            'tracking_token'        => 'tok-' . uniqid(),
            'first_name'            => 'Aday',
            'last_name'             => 'Test',
            'email'                 => 'aday-' . uniqid() . '@example.test',
            'application_type'      => 'bachelor',
            'selected_package_code' => $code,
        ]));
    }

    public function test_ekran_aciliyor_ve_miras_kaynagini_soyluyor(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        CompanyServicePackage::create([
            'company_id' => $this->companyA->id, 'code' => 'pkg_ust', 'title' => 'Üst Paket',
            'price_amount' => 4000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);

        $response = $this->asStaff($this->managerOf($this->companyB))->get('/manager/services');

        $response->assertOk();
        $response->assertSee('Firma A', false);
        $response->assertSee('Üst Paket', false);
        $response->assertSee('Kendi kataloğumu oluştur', false);
    }

    public function test_kopyalama_mirasi_kendi_katalogu_yapar(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        CompanyServicePackage::create([
            'company_id' => $this->companyA->id, 'code' => 'pkg_ust', 'title' => 'Üst Paket',
            'price_amount' => 4000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->asStaff($this->managerOf($this->companyB))
            ->post('/manager/services/fork')
            ->assertRedirect(route('manager.services.index'));

        $this->assertTrue(ServiceCatalog::hasOwnCatalog((int) $this->companyB->id));
        $this->assertSame(4000.0, ServiceCatalog::quote('pkg_ust', [], (int) $this->companyB->id));
    }

    /** Kopyalarken pasifler de gelmeli, yoksa eski seçimler çözülemez. */
    public function test_kopyalama_pasif_paketleri_de_alir(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        CompanyServicePackage::create([
            'company_id' => $this->companyA->id, 'code' => 'pkg_eski', 'title' => 'Kaldırılmış',
            'price_amount' => 1500, 'currency' => 'EUR', 'is_active' => false, 'sort_order' => 1,
        ]);

        $this->asStaff($this->managerOf($this->companyB))->post('/manager/services/fork');

        $this->assertSame(1500.0, ServiceCatalog::quote('pkg_eski', [], (int) $this->companyB->id));
    }

    public function test_fiyat_guncellenebiliyor(): void
    {
        $package = CompanyServicePackage::create([
            'company_id' => $this->companyA->id, 'code' => 'pkg_start', 'title' => 'Başlangıç',
            'price_amount' => 2000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->asStaff($this->managerOf($this->companyA))
            ->patch('/manager/services/packages/' . $package->id, [
                'code'         => 'pkg_start',
                'title'        => 'Başlangıç',
                'price_amount' => 3250,
                'currency'     => 'EUR',
                'features'     => "Üniversite seçimi\nBaşvuru dosyası",
                'is_active'    => '1',
            ])
            ->assertSessionHasNoErrors();

        $package->refresh();

        $this->assertSame(3250.0, (float) $package->price_amount);
        $this->assertSame(['Üniversite seçimi', 'Başvuru dosyası'], $package->features);
        // Gösterim fiyatı sayıdan üretilmeli — iki alan birbirinden kopmasın.
        $this->assertStringContainsString('3.250', (string) $package->price);
    }

    public function test_kullanimdaki_paket_silinmez_pasife_alinir(): void
    {
        $package = CompanyServicePackage::create([
            'company_id' => $this->companyA->id, 'code' => 'pkg_start', 'title' => 'Başlangıç',
            'price_amount' => 2000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->guestWithPackage($this->companyA, 'pkg_start');

        $this->asStaff($this->managerOf($this->companyA))
            ->delete('/manager/services/packages/' . $package->id);

        $this->assertDatabaseHas('company_service_packages', ['id' => $package->id, 'is_active' => false]);
        // Geçmiş kayıt hâlâ çözülebilmeli.
        $this->assertSame(2000.0, ServiceCatalog::quote('pkg_start', [], (int) $this->companyA->id));
    }

    public function test_kullanilmayan_paket_silinir(): void
    {
        $package = CompanyServicePackage::create([
            'company_id' => $this->companyA->id, 'code' => 'pkg_bos', 'title' => 'Kullanılmayan',
            'price_amount' => 500, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->asStaff($this->managerOf($this->companyA))
            ->delete('/manager/services/packages/' . $package->id);

        $this->assertDatabaseMissing('company_service_packages', ['id' => $package->id]);
    }

    /**
     * Mirasa dönüş, kullanımdaki bir kodu üst katalogda bulamıyorsa
     * engellenmeli — yoksa o adayların tutarı çözülemez hâle gelir.
     */
    public function test_mirasa_donus_yetim_kod_birakacaksa_engellenir(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        CompanyServicePackage::create([
            'company_id' => $this->companyA->id, 'code' => 'pkg_ust', 'title' => 'Üst',
            'price_amount' => 1000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);
        CompanyServicePackage::create([
            'company_id' => $this->companyB->id, 'code' => 'pkg_ozel', 'title' => 'Özel',
            'price_amount' => 5000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->guestWithPackage($this->companyB, 'pkg_ozel');

        $this->asStaff($this->managerOf($this->companyB))
            ->post('/manager/services/reset')
            ->assertSessionHasErrors('catalog');

        $this->assertTrue(ServiceCatalog::hasOwnCatalog((int) $this->companyB->id));
    }

    public function test_mirasa_donus_guvenliyse_calisir(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        CompanyServicePackage::create([
            'company_id' => $this->companyA->id, 'code' => 'pkg_ortak', 'title' => 'Üst',
            'price_amount' => 1000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);
        CompanyServicePackage::create([
            'company_id' => $this->companyB->id, 'code' => 'pkg_ortak', 'title' => 'Alt',
            'price_amount' => 5000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);
        CompanyServiceExtra::create([
            'company_id' => $this->companyB->id, 'code' => 'ext_x', 'title' => 'Ek',
            'price_amount' => 100, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->guestWithPackage($this->companyB, 'pkg_ortak');

        $this->asStaff($this->managerOf($this->companyB))
            ->post('/manager/services/reset')
            ->assertSessionHasNoErrors();

        $this->assertFalse(ServiceCatalog::hasOwnCatalog((int) $this->companyB->id));
        $this->assertSame(1000.0, ServiceCatalog::quote('pkg_ortak', [], (int) $this->companyB->id));
    }

    /** Yatay izolasyon: başka firmanın paketi düzenlenemez. */
    public function test_baska_firmanin_paketi_duzenlenemez(): void
    {
        $foreign = CompanyServicePackage::create([
            'company_id' => $this->companyA->id, 'code' => 'pkg_a', 'title' => 'A Paketi',
            'price_amount' => 2000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);

        CompanyServicePackage::create([
            'company_id' => $this->companyB->id, 'code' => 'pkg_b', 'title' => 'B Paketi',
            'price_amount' => 3000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->asStaff($this->managerOf($this->companyB))
            ->patch('/manager/services/packages/' . $foreign->id, [
                'code' => 'pkg_a', 'title' => 'Ele geçirildi', 'price_amount' => 1,
            ])
            ->assertNotFound();

        $this->assertSame(2000.0, (float) $foreign->refresh()->price_amount);
    }

    public function test_ayni_kod_iki_kez_eklenemez(): void
    {
        CompanyServicePackage::create([
            'company_id' => $this->companyA->id, 'code' => 'pkg_start', 'title' => 'Başlangıç',
            'price_amount' => 2000, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->asStaff($this->managerOf($this->companyA))
            ->post('/manager/services/packages', [
                'code' => 'pkg_start', 'title' => 'Kopya', 'price_amount' => 999,
            ])
            ->assertStatus(422);
    }
}
