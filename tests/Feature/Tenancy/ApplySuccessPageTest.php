<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Başvuru sonrası "başarılı" sayfası.
 *
 * ── CANLIDA YAKALANAN HATA ──────────────────────────────────────────────
 * Partner firmanın başvuru linkinden kayıt olan öğrenci, başarı sayfasında
 * 404 alıyordu. Kayıt oluşuyordu (takip kodu üretiliyordu) ama sayfa
 * açılmıyordu.
 *
 * Sebep: takip kodu ŞİRKET KAPSAMLI aranıyordu. Başvuran anonimdir ve
 * bağlamı varsayılan şirkettir; kayıt ise partnerin kutusundadır → bulunamaz.
 *
 * Takip kodu 12 karakterlik rastgele bir sırdır; onu bilmek yetkinin
 * kendisidir (imzalı URL mantığı). Arama kapsam dışı olmalı.
 */
class ApplySuccessPageTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function leadIn(Company $company, string $token, string $email): GuestApplication
    {
        return TenantContext::runFor((int) $company->id, fn (): GuestApplication => GuestApplication::create([
            'tracking_token' => $token,
            'first_name' => 'Basari',
            'last_name' => 'Sayfasi',
            'email' => $email,
            'application_type' => 'bachelor',
        ]));
    }

    // ── Asıl regresyon ──────────────────────────────────────────────────────

    /** Partner firmanın adayı kendi başarı sayfasını GÖREBİLMELİ. */
    public function test_partner_applicant_can_open_the_success_page(): void
    {
        $this->leadIn($this->companyB, 'PARTNERTOKEN', 'partner-basari@example.test');

        $this->get('/apply/success?token=PARTNERTOKEN')
            ->assertOk()
            ->assertSee('PARTNERTOKEN', false);
    }

    public function test_default_company_applicant_still_works(): void
    {
        $default = Company::query()->whereRaw('lower(code) = ?', ['mentorde'])->firstOrFail();

        $this->leadIn($default, 'B2CTOKEN1234', 'b2c-basari@example.test');

        $this->get('/apply/success?token=B2CTOKEN1234')->assertOk();
    }

    // ── Kod hâlâ bir sır ────────────────────────────────────────────────────

    public function test_unknown_token_is_rejected(): void
    {
        $this->get('/apply/success?token=OLMAYANKOD12')->assertNotFound();
    }

    public function test_missing_token_is_rejected(): void
    {
        $this->get('/apply/success')->assertNotFound();
    }

    /**
     * Kapsam dışı arama, KOD BİLMEDEN erişim sağlamamalı. Sayfa yalnızca
     * doğru kodu bilene açılır; liste ya da tarama yolu yoktur.
     */
    public function test_page_does_not_leak_other_applications(): void
    {
        $this->leadIn($this->companyA, 'AAAAAAAAAAAA', 'a@example.test');
        $this->leadIn($this->companyB, 'BBBBBBBBBBBB', 'b@example.test');

        $this->get('/apply/success?token=AAAAAAAAAAAA')
            ->assertOk()
            ->assertDontSee('b@example.test', false)
            ->assertDontSee('BBBBBBBBBBBB', false);
    }

    // ── Takip kodu benzersizliği ────────────────────────────────────────────

    /**
     * Benzersizlik kontrolü KAPSAM DIŞI olmalı: kapsamlı sorgu başka
     * firmadaki kodu göremez ve ÇAKIŞAN kod üretirdi.
     */
    public function test_token_generation_sees_tokens_of_all_companies(): void
    {
        $taken = [];

        // Farklı şirketlerde 30 kod üret, hepsi tekil olmalı
        foreach (range(1, 30) as $i) {
            $company = $i % 2 === 0 ? $this->companyA : $this->companyB;

            $token = TenantContext::runFor(
                (int) $company->id,
                fn (): string => GuestApplication::generateTrackingToken()
            );

            $this->assertNotContains($token, $taken, 'Cakisan takip kodu uretildi.');

            $taken[] = $token;

            TenantContext::runFor((int) $company->id, fn () => GuestApplication::create([
                'tracking_token' => $token,
                'first_name' => 'Kod',
                'last_name' => (string) $i,
                'email' => "kod{$i}@example.test",
                'application_type' => 'bachelor',
            ]));
        }

        $this->assertCount(30, array_unique($taken));
    }

    /** Model yardımcısı kapsamdan bağımsız bulmalı. */
    public function test_finder_works_from_any_context(): void
    {
        $this->leadIn($this->companyB, 'FINDERTOKEN1', 'finder@example.test');

        $found = TenantContext::runFor(
            (int) $this->companyA->id,
            fn () => GuestApplication::findByTrackingToken('FINDERTOKEN1')
        );

        $this->assertNotNull($found);
        $this->assertSame((int) $this->companyB->id, (int) $found->company_id);
    }
}
