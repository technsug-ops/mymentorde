<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use App\Services\AdvisorAssignmentService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Elle aday girişi.
 *
 * Sisteme aday sokmanın tek yolu public başvuru formuydu; telefonda konuşulan
 * bir öğrenciyi kaydetmek için firmanın formu öğrenci adına doldurması
 * gerekiyordu (CAPTCHA + e-posta doğrulaması yüzünden pratikte zahmetli).
 *
 * Kayıt İÇİNDE BULUNULAN ŞİRKET BAĞLAMINA yazılır — personel şirket
 * değiştiriciyle partnere geçmişse aday partnere düşer.
 */
class ManualLeadEntryTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /**
     * Manager rotalari `require.2fa` arkasinda. Middleware `2fa_passed`
     * session bayragiyla geciyor; testte onu kurmazsak POST controller'a
     * hic ulasmaz ve test app'i degil 2FA kapisini olcer.
     */
    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    /** @return array<string,string> */
    private function payload(string $email, array $extra = []): array
    {
        return array_merge([
            'first_name' => 'Telefonla',
            'last_name' => 'Gelen',
            'email' => $email,
            'phone' => '+90 5321234567',
            'application_type' => 'bachelor',
            'target_term' => '2027 Summer',
        ], $extra);
    }

    private function advisorIn(Company $company): User
    {
        $advisor = User::create([
            'name' => 'Danisman',
            'email' => 'danisman-' . $company->code . '@example.test',
            'password' => bcrypt('gizli-sifre-123'),
            'role' => User::ROLE_SENIOR,
            'is_active' => true,
            'auto_assign_enabled' => true,
            'company_id' => $company->id,
        ]);

        Company::flushAdvisorCache();

        return $advisor;
    }

    // ── Temel kayıt ─────────────────────────────────────────────────────────

    public function test_manager_can_create_a_lead_manually(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->post('/manager/leads', $this->payload('elle@example.test'))
            ->assertRedirect();

        $lead = GuestApplication::query()->withoutGlobalScope('company')
            ->where('email', 'elle@example.test')->first();

        $this->assertNotNull($lead, 'Elle girilen aday olusmadi.');
        $this->assertSame((int) $this->companyB->id, (int) $lead->company_id);
        $this->assertSame('new', (string) $lead->lead_status);
    }

    /** Kaynağı belirtilmezse "elle girilmiş" olarak işaretlensin — organik sanılmasın. */
    public function test_manual_entry_is_marked_as_such(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->post('/manager/leads', $this->payload('kaynak@example.test'))
            ->assertRedirect();

        $lead = GuestApplication::query()->withoutGlobalScope('company')
            ->where('email', 'kaynak@example.test')->firstOrFail();

        $this->assertSame('manual_entry', (string) $lead->lead_source);
    }

    // ── Danışman ataması ────────────────────────────────────────────────────

    /** Elle girilen adaya da OPERASYON şirketinden danışman atanmalı. */
    public function test_manual_lead_gets_an_advisor_from_the_operating_company(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        $advisor = $this->advisorIn($this->companyA);

        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->asStaff($partnerManager)
            ->post('/manager/leads', $this->payload('danismanli@example.test'))
            ->assertRedirect();

        $lead = GuestApplication::query()->withoutGlobalScope('company')
            ->where('email', 'danismanli@example.test')->firstOrFail();

        $this->assertSame(
            $advisor->email,
            (string) $lead->assigned_senior_email,
            'Elle girilen adaya operasyon sirketinden danisman atanmadi.'
        );
    }

    /** Danışman bulunamazsa kayıt yine oluşur — sessizce kaybolmaz. */
    public function test_lead_is_created_even_without_an_advisor(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->post('/manager/leads', $this->payload('danismansiz@example.test'))
            ->assertRedirect();

        $lead = GuestApplication::query()->withoutGlobalScope('company')
            ->where('email', 'danismansiz@example.test')->firstOrFail();

        $this->assertNull($lead->assigned_senior_email);
    }

    // ── Bağlam ──────────────────────────────────────────────────────────────

    /**
     * Personel şirket değiştiriciyle partnere geçtiyse aday PARTNERE düşmeli.
     * Elle girişin şirket değiştiriciyle birlikte çalışması bunun için önemli.
     */
    public function test_lead_lands_in_the_switched_company(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->asStaff($manager)->post('/company-context/switch', ['company_id' => $this->companyB->id]);

        $this->asStaff($manager)
            ->post('/manager/leads', $this->payload('baglamli@example.test'))
            ->assertRedirect();

        $lead = GuestApplication::query()->withoutGlobalScope('company')
            ->where('email', 'baglamli@example.test')->firstOrFail();

        $this->assertSame(
            (int) $this->companyB->id,
            (int) $lead->company_id,
            'Aday degistirilen firmaya degil kendi sirketine yazildi.'
        );
    }

    // ── Mükerrer koruması ───────────────────────────────────────────────────

    public function test_duplicate_email_in_the_same_company_is_rejected(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->asStaff($manager)->post('/manager/leads', $this->payload('mukerrer@example.test'));

        $this->asStaff($manager)
            ->post('/manager/leads', $this->payload('mukerrer@example.test'))
            ->assertSessionHasErrors('email');

        $this->assertSame(
            1,
            GuestApplication::query()->withoutGlobalScope('company')
                ->where('email', 'mukerrer@example.test')->count()
        );
    }

    /**
     * BAŞKA firmadaki aynı e-posta engel OLMAMALI — engel olsaydı
     * "bu kişi rakipte de var" bilgisi sızardı.
     */
    public function test_same_email_in_another_company_does_not_block(): void
    {
        TenantContext::runFor((int) $this->companyA->id, fn () => GuestApplication::create([
            'tracking_token' => 'tok-' . uniqid(),
            'first_name' => 'Baska',
            'last_name' => 'Firmada',
            'email' => 'ortak@example.test',
            'application_type' => 'bachelor',
        ]));

        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->post('/manager/leads', $this->payload('ortak@example.test'))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(
            2,
            GuestApplication::query()->withoutGlobalScope('company')
                ->where('email', 'ortak@example.test')->count()
        );
    }

    // ── Doğrulama ve yetki ──────────────────────────────────────────────────

    public function test_email_is_required(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->post('/manager/leads', $this->payload('') + ['email' => ''])
            ->assertSessionHasErrors('email');
    }

    public function test_students_cannot_create_leads(): void
    {
        $student = $this->userFor($this->companyB, User::ROLE_STUDENT);

        $response = $this->asStaff($student)
            ->post('/manager/leads', $this->payload('ogrenci@example.test'));

        $this->assertContains($response->getStatusCode(), [403, 404, 302]);
        $this->assertNull(
            GuestApplication::query()->withoutGlobalScope('company')
                ->where('email', 'ogrenci@example.test')->first()
        );
    }

    public function test_guests_cannot_create_leads(): void
    {
        $response = $this->post('/manager/leads', $this->payload('anonim@example.test'));

        $this->assertContains($response->getStatusCode(), [401, 403, 404, 302]);
    }

    // ── Servis ──────────────────────────────────────────────────────────────

    /** Danışman seçimi tek yerde — üç çağıran da aynı kuralı kullanmalı. */
    public function test_advisor_service_is_shared_by_all_entry_points(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        $advisor = $this->advisorIn($this->companyA);

        $this->assertSame(
            $advisor->email,
            app(AdvisorAssignmentService::class)->pickFor((int) $this->companyB->id, 'bachelor')
        );
    }
}
