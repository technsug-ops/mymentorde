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
 * Platform konsolu — HACİM görünümü.
 *
 * ⚠ KİŞİSEL VERİ GÖSTERMEZ. BİLEREK.
 *
 * DGmarkt yazılım servisi sağlar; müşterilerinin öğrencileri için veri
 * sorumlusu değildir. Ad, e-posta, telefon bu konsolda listelenirse KVKK/GDPR
 * açısından savunulamaz — servis sağlayıcının müşterinin müşterisini tanımasını
 * gerektiren bir iş gerekçesi yok.
 *
 * Bu testler ekranın SAYI gösterdiğini ve KİŞİ göstermediğini birlikte doğrular;
 * ikincisi olmadan ilki eksik kalır.
 */
class PlatformPortfolioTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function owner(): User
    {
        return $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
    }

    private function seedLead(Company $company, string $first, string $last, string $email): GuestApplication
    {
        return TenantContext::runFor((int) $company->id, fn (): GuestApplication => GuestApplication::create([
            'tracking_token' => 'tok-' . uniqid(),
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => '+49 15123456789',
            'application_type' => 'bachelor',
            'lead_status' => 'qualified',
        ]));
    }

    // ── Kişisel veri sızmamalı ──────────────────────────────────────────────

    public function test_lead_page_shows_no_personal_data(): void
    {
        $this->seedLead($this->companyA, 'Mahremiyet', 'Testi', 'mahrem@example.test');
        $this->seedLead($this->companyB, 'Baskasi', 'Kisi', 'baskasi@example.test');

        $response = $this->actingAs($this->owner())->get('/platform/leads');

        $response->assertOk();

        foreach (['Mahremiyet', 'mahrem@example.test', 'Baskasi', 'baskasi@example.test', '15123456789'] as $pii) {
            $response->assertDontSee($pii, false);
        }
    }

    public function test_student_page_shows_no_personal_data(): void
    {
        $student = $this->userFor($this->companyB, User::ROLE_STUDENT);

        $response = $this->actingAs($this->owner())->get('/platform/students');

        $response->assertOk();
        $response->assertDontSee($student->email, false);
    }

    /** Arama kutusu da olmamalı — e-posta ile kişi aramak da kişisel veri işlemektir. */
    public function test_lead_page_has_no_person_search(): void
    {
        $this->actingAs($this->owner())
            ->get('/platform/leads')
            ->assertOk()
            ->assertDontSee('name="q"', false);
    }

    /**
     * SATIŞ HUNİSİ de gösterilmemeli.
     *
     * "Kaç aday nitelikli, kaçı teklif aşamasında" musterinin kendi
     * operasyonudur; servis saglayiciyi ilgilendirmez. Platform konsolu
     * yalnizca KAPASITE gosterir.
     */
    public function test_lead_page_shows_no_sales_pipeline(): void
    {
        $this->seedLead($this->companyA, 'Huni', 'Testi', 'huni@example.test');

        $response = $this->actingAs($this->owner())->get('/platform/leads');

        $response->assertOk();
        $response->assertDontSee('Durum Dağılımı', false);
        $response->assertDontSee('İletişime geçildi', false);
        $response->assertDontSee('Nitelikli', false);
    }

    // ── Kota görünümü ───────────────────────────────────────────────────────

    public function test_lead_page_shows_quota_usage(): void
    {
        $this->companyB->update(['subscription_tier' => Company::TIER_TRIAL]);
        $this->seedLead($this->companyB, 'Kota', 'Testi', 'kota@example.test');

        $response = $this->actingAs($this->owner())->get('/platform/leads');

        $response->assertOk();
        $response->assertSee($this->companyB->name, false);
        $response->assertSee('Kota Kullanımı', false);
        // Trial paketinin aday limiti
        $response->assertSee((string) config('subscription_tiers.trial.limits.leads_max'), false);
    }

    /** Limitine dayanan firma "üst pakete geçmeli" diye işaretlenmeli. */
    public function test_company_over_its_limit_is_flagged(): void
    {
        $this->companyB->update(['subscription_tier' => Company::TIER_TRIAL]);

        $limit = (int) config('subscription_tiers.trial.limits.students_max');

        for ($i = 0; $i < $limit; $i++) {
            $this->userFor($this->companyB, User::ROLE_STUDENT);
        }

        $response = $this->actingAs($this->owner())->get('/platform/students');

        $response->assertOk();
        $response->assertSee('Limit doldu', false);
    }

    public function test_unlimited_tier_shows_no_percentage(): void
    {
        $this->companyB->update(['subscription_tier' => Company::TIER_PREMIUM]);

        $this->actingAs($this->owner())
            ->get('/platform/students')
            ->assertOk()
            ->assertSee('Sınırsız paket', false);
    }

    public function test_student_page_shows_company_totals(): void
    {
        $this->userFor($this->companyB, User::ROLE_STUDENT);

        $this->actingAs($this->owner())
            ->get('/platform/students')
            ->assertOk()
            ->assertSee('Toplam Öğrenci', false)
            ->assertSee($this->companyB->name, false);
    }

    // ── Yetki ───────────────────────────────────────────────────────────────

    public function test_company_users_cannot_reach_the_console(): void
    {
        $firmUser = $this->userFor($this->companyB, User::ROLE_MANAGER);

        foreach (['/platform/leads', '/platform/students'] as $url) {
            $response = $this->actingAs($firmUser)->get($url);
            $this->assertContains($response->getStatusCode(), [403, 404, 302]);
        }
    }

    public function test_guests_cannot_reach_the_console(): void
    {
        foreach (['/platform/leads', '/platform/students'] as $url) {
            $response = $this->get($url);
            $this->assertContains($response->getStatusCode(), [401, 403, 404, 302]);
        }
    }

    // ── Devir buraya AİT DEĞİL ──────────────────────────────────────────────

    /**
     * Aday devri operasyonel bir karardır; süreci yürüten firmayı ilgilendirir.
     * Yazılım servisi sağlayıcısının müşterisinin adayını taşıması savunulamaz.
     * Konsolda ne formu ne de uç noktası olmamalı.
     */
    public function test_transfer_is_not_offered_in_the_console(): void
    {
        $this->actingAs($this->owner())
            ->get('/platform/leads')
            ->assertOk()
            ->assertDontSee('Aday Devri', false)
            ->assertDontSee('Devret', false);
    }

    public function test_transfer_endpoint_is_gone_from_the_platform(): void
    {
        $lead = $this->seedLead($this->companyA, 'Devir', 'Testi', 'devir@example.test');

        $response = $this->actingAs($this->owner())
            ->post('/platform/leads/' . $lead->id . '/transfer', ['company_id' => $this->companyB->id]);

        $this->assertContains($response->getStatusCode(), [404, 405]);

        $this->assertSame(
            (int) $this->companyA->id,
            (int) GuestApplication::withoutGlobalScope('company')->find($lead->id)->company_id,
            'Kaldirilmis uc nokta hala calisiyor.'
        );
    }

    /** Konsol sorgusu normal tenant sorgularını kirletmemeli. */
    public function test_console_does_not_leak_context_into_normal_queries(): void
    {
        $this->seedLead($this->companyB, 'Baglam', 'Testi', 'baglam@example.test');

        $this->actingAs($this->owner())->get('/platform/leads')->assertOk();

        $seenByA = TenantContext::runFor(
            (int) $this->companyA->id,
            fn () => GuestApplication::query()->where('email', 'baglam@example.test')->first()
        );

        $this->assertNull($seenByA, 'Konsol sorgusu tenant filtresini bozdu.');
    }
}
