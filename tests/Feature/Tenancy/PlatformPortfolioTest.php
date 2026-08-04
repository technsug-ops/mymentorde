<?php

namespace Tests\Feature\Tenancy;

use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * KONSOLİDE PORTFÖY (Faz 5) — platform sahibinin "hepsini tek yerde" görünümü.
 *
 * Bu sayfalar tenant izolasyonunun BİLİNÇLİ istisnasıdır: platform sahibi tüm
 * şirketlerin adaylarını/öğrencilerini tek listede görür. O yüzden erişim
 * kontrolü kritik — firma kullanıcısı buraya asla giremez.
 */
class PlatformPortfolioTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function owner(): User
    {
        return $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
    }

    private function seedLeads(): void
    {
        foreach ([[$this->companyA, 'aday-a@example.test', 'Ayse'], [$this->companyB, 'aday-b@example.test', 'Burak']] as [$company, $email, $name]) {
            GuestApplication::withoutGlobalScope('company')->create([
                'company_id' => $company->id,
                'tracking_token' => 'TOK-' . strtoupper(substr(md5($email), 0, 8)),
                'first_name' => $name,
                'last_name' => 'Test',
                'email' => $email,
                'application_type' => 'bachelor',
                'kvkk_consent' => true,
                'lead_status' => 'new',
            ]);
        }
    }

    // ── Erişim kontrolü ─────────────────────────────────────────────────────

    public function test_company_users_cannot_reach_the_consolidated_lists(): void
    {
        $firmUser = $this->userFor($this->companyA, User::ROLE_MARKETING_ADMIN);

        foreach (['/platform/leads', '/platform/students'] as $path) {
            $response = $this->actingAs($firmUser)->get($path);

            $this->assertContains(
                $response->getStatusCode(),
                [403, 404, 302],
                "Firma kullanıcısı {$path} sayfasına erişebildi — cross-tenant sızıntı."
            );
        }
    }

    public function test_guests_cannot_reach_the_consolidated_lists(): void
    {
        $this->get('/platform/leads')->assertRedirect();
    }

    // ── Platform sahibi tüm şirketleri görür ────────────────────────────────

    public function test_owner_sees_leads_from_every_company(): void
    {
        $this->seedLeads();

        $html = $this->actingAs($this->owner())->get('/platform/leads')->assertOk()->getContent();

        $this->assertStringContainsString('aday-a@example.test', $html);
        $this->assertStringContainsString('aday-b@example.test', $html, 'Diğer şirketin adayı konsolide listede yok.');

        // Her satır hangi şirkete ait olduğunu göstermeli
        $this->assertStringContainsString('Firma A', $html);
        $this->assertStringContainsString('Firma B', $html);
    }

    public function test_company_filter_narrows_the_list(): void
    {
        $this->seedLeads();

        $html = $this->actingAs($this->owner())
            ->get('/platform/leads?company=' . $this->companyB->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aday-b@example.test', $html);
        $this->assertStringNotContainsString('aday-a@example.test', $html, 'Şirket filtresi çalışmıyor.');
    }

    public function test_search_filters_across_companies(): void
    {
        $this->seedLeads();

        $html = $this->actingAs($this->owner())
            ->get('/platform/leads?q=Burak')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aday-b@example.test', $html);
        $this->assertStringNotContainsString('aday-a@example.test', $html);
    }

    public function test_owner_sees_students_from_every_company(): void
    {
        $studentA = $this->userFor($this->companyA, User::ROLE_STUDENT);
        $studentB = $this->userFor($this->companyB, User::ROLE_STUDENT);

        $html = $this->actingAs($this->owner())->get('/platform/students')->assertOk()->getContent();

        $this->assertStringContainsString($studentA->email, $html);
        $this->assertStringContainsString($studentB->email, $html);
    }

    /**
     * Konsolide sayfalar izolasyonu BOZMAMALI: aynı istekten sonra normal
     * sorgular hâlâ şirketle sınırlı kalmalı (bağlam sızmamalı).
     */
    public function test_consolidated_view_does_not_leak_context_into_normal_queries(): void
    {
        $this->seedLeads();

        $this->actingAs($this->owner())->get('/platform/leads')->assertOk();

        \App\Support\TenantContext::bind($this->companyA->id, [$this->companyA->id]);

        $emails = GuestApplication::query()->pluck('email')->all();

        $this->assertContains('aday-a@example.test', $emails);
        $this->assertNotContains('aday-b@example.test', $emails, 'Konsolide görünüm sonrası scope sızdı.');
    }
}
