<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Danışman ataması — partner öğrencisine MentorDE'nin danışmanı atanır.
 *
 * İş modeli: partner firma öğrenciyi DIŞARIDAN verir, süreci MentorDE yürütür.
 * Partner firmanın kendi danışmanı yoktur ve MentorDE'nin danışmanına dışarıdan
 * görev veremez — atama MentorDE'nin kararıdır.
 *
 * Buradaki regresyon SESSİZDİ: danışman sorgusu şirket kapsamlıydı, partner
 * bağlamında hiç danışman bulunamıyor ve öğrenci ATANMAMIŞ kalıyordu.
 */
class AdvisorAssignmentTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /** companyA = operasyonu yürüten (MentorDE rolünde), companyB = partner. */
    private function makeOperatingHierarchy(): User
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();
        Company::flushAdvisorCache();

        $advisor = User::create([
            'name' => 'MentorDE Danismani',
            'email' => 'danisman@mentorde.test',
            'password' => bcrypt('gizli-sifre-123'),
            'role' => User::ROLE_SENIOR,
            'is_active' => true,
            'auto_assign_enabled' => true,
            'company_id' => $this->companyA->id,
        ]);

        Company::flushAdvisorCache();

        return $advisor;
    }

    // ── Operasyon şirketi çözümlemesi ───────────────────────────────────────

    public function test_partner_company_resolves_to_the_operating_parent(): void
    {
        $this->makeOperatingHierarchy();

        $this->assertSame(
            (int) $this->companyA->id,
            Company::operatingCompanyId((int) $this->companyB->id),
            'Partner firma icin operasyon sirketi bulunamadi.'
        );
    }

    /** Kendi danışmanı olan firma kendisini kullanır — kimseye bağlanmaz. */
    public function test_company_with_its_own_advisors_operates_itself(): void
    {
        $this->makeOperatingHierarchy();

        User::create([
            'name' => 'Partner Danismani',
            'email' => 'danisman@partner.test',
            'password' => bcrypt('gizli-sifre-123'),
            'role' => User::ROLE_SENIOR,
            'is_active' => true,
            'auto_assign_enabled' => true,
            'company_id' => $this->companyB->id,
        ]);
        Company::flushAdvisorCache();

        $this->assertSame(
            (int) $this->companyB->id,
            Company::operatingCompanyId((int) $this->companyB->id)
        );
    }

    public function test_company_without_any_advisor_in_the_chain_returns_null(): void
    {
        $lonely = Company::create(['name' => 'Yalniz', 'code' => 'yalniz', 'is_active' => true]);
        Company::flushAdvisorCache();

        $this->assertNull(Company::operatingCompanyId((int) $lonely->id));
    }

    // ── Başvuruda otomatik atama ────────────────────────────────────────────

    /**
     * EN KRİTİK: partner linkinden gelen başvuruya MentorDE danışmanı atanmalı.
     * Eskiden sessizce boş kalıyordu.
     */
    public function test_partner_application_gets_an_advisor_from_the_operating_company(): void
    {
        $advisor = $this->makeOperatingHierarchy();

        // Partner firmanın başvuru kabul edebilmesi için personeli olmalı
        $this->userFor($this->companyB, User::ROLE_MANAGER);
        $this->companyB->update(['slug' => 'firma-b']);

        $this->get('/apply/firma-b')->assertOk();

        $this->post('/apply', [
            'first_name' => 'Partner',
            'last_name' => 'Ogrencisi',
            'email' => 'partner-ogrenci@example.test',
            'phone' => '+49 15123456789',
            'application_type' => 'bachelor',
            'kvkk_consent' => '1',
        ])->assertRedirect();

        $lead = GuestApplication::query()->withoutGlobalScope('company')
            ->where('email', 'partner-ogrenci@example.test')->firstOrFail();

        $this->assertSame((int) $this->companyB->id, (int) $lead->company_id);
        $this->assertSame(
            $advisor->email,
            (string) $lead->assigned_senior_email,
            'Partner ogrencisine MentorDE danismani ATANMADI.'
        );
    }

    /** Kapasite danışmanın TOPLAM yükü — şirkete göre sayılmaz. */
    public function test_advisor_capacity_counts_students_across_all_companies(): void
    {
        $advisor = $this->makeOperatingHierarchy();
        $advisor->update(['max_capacity' => 1]);

        // Danışmanın MEVCUT yükü üst firmada
        TenantContext::runFor((int) $this->companyA->id, fn () => StudentAssignment::create([
            'student_id' => 'STU-DOLU',
            'senior_email' => $advisor->email,
            'risk_level' => 'normal',
            'payment_status' => 'ok',
            'is_archived' => false,
        ]));

        $this->userFor($this->companyB, User::ROLE_MANAGER);
        $this->companyB->update(['slug' => 'firma-b']);

        $this->get('/apply/firma-b')->assertOk();
        $this->post('/apply', [
            'first_name' => 'Dolu',
            'last_name' => 'Danisman',
            'email' => 'dolu@example.test',
            'phone' => '+49 15123456789',
            'application_type' => 'bachelor',
            'kvkk_consent' => '1',
        ])->assertRedirect();

        $lead = GuestApplication::query()->withoutGlobalScope('company')
            ->where('email', 'dolu@example.test')->firstOrFail();

        $this->assertNotSame(
            $advisor->email,
            (string) $lead->assigned_senior_email,
            'Kapasitesi dolu danismana atama yapildi — yuk sirkete gore sayiliyor.'
        );
    }

    // ── Partner atama YAPAMAZ ───────────────────────────────────────────────

    /**
     * Partner firma MentorDE'nin danışmanına dışarıdan görev veremez.
     * Yetki tavanı bunu kapatabilmeli.
     */
    public function test_partner_can_be_denied_advisor_assignment(): void
    {
        $this->makeOperatingHierarchy();

        $this->companyB->update(['denied_permission_codes' => ['student.assignment.manage']]);
        Company::flushHierarchyCache();

        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->assertFalse(
            $partnerManager->hasPermissionCode('student.assignment.manage'),
            'Partner firma hala danisman atayabiliyor.'
        );
    }

    /** Operasyon şirketinin kendi personeli atama yapabilmeye devam etmeli. */
    public function test_operating_company_staff_keeps_assignment_permission(): void
    {
        $this->makeOperatingHierarchy();

        $this->companyB->update(['denied_permission_codes' => ['student.assignment.manage']]);
        Company::flushHierarchyCache();

        $mentordeManager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->assertTrue(
            $mentordeManager->hasPermissionCode('student.assignment.manage'),
            'Kisit yanlis tarafa uygulandi — operasyon ekibi atama yapamiyor.'
        );
    }
}
