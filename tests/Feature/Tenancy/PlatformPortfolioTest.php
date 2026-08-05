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

    // ── Sayılar doğru olmalı ────────────────────────────────────────────────

    public function test_lead_page_shows_counts_per_company(): void
    {
        $this->seedLead($this->companyA, 'Bir', 'Aday', 'bir@example.test');
        $this->seedLead($this->companyB, 'Iki', 'Aday', 'iki@example.test');
        $this->seedLead($this->companyB, 'Uc', 'Aday', 'uc@example.test');

        $response = $this->actingAs($this->owner())->get('/platform/leads');

        $response->assertOk();
        $response->assertSee($this->companyA->name, false);
        $response->assertSee($this->companyB->name, false);
        $response->assertSee('Toplam Aday', false);
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

    // ── Devir hâlâ çalışmalı ────────────────────────────────────────────────

    /** Devir kişisel veri göstermeden, ID ile çalışır. */
    public function test_transfer_still_works_by_id(): void
    {
        $lead = $this->seedLead($this->companyA, 'Devir', 'Testi', 'devir@example.test');

        $this->actingAs($this->owner())
            ->post('/platform/leads/' . $lead->id . '/transfer', ['company_id' => $this->companyB->id])
            ->assertRedirect();

        $this->assertSame(
            (int) $this->companyB->id,
            (int) GuestApplication::withoutGlobalScope('company')->find($lead->id)->company_id
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
