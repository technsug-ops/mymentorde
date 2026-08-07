<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\AdvisorAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Partner firmanın varsayılan danışmanını ÜST FİRMA seçer.
 *
 * ── SORUN ───────────────────────────────────────────────────────────────
 * Otomatik atama en az yüklü danışmanı seçiyor. Yükler eşitken sıralama hep
 * aynı kişiyi öne çıkarıyor; pratikte her yeni aday aynı danışmana düşüyordu.
 *
 * ── SINIR ───────────────────────────────────────────────────────────────
 * Seçim kapasite kuralını DELMEZ. Seçilen danışman pasifse, otomatik
 * atamaya kapalıysa ya da doluysa sistem normal dağıtıma döner — aksi halde
 * "seçildi" diye dolu bir danışmana yığılırdı.
 */
class DefaultAdvisorTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /** companyA = operasyon (danışmanlar burada), companyB = partner. */
    private function linkPartner(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();
        Company::flushAdvisorCache();
    }

    private function advisor(array $attributes = []): User
    {
        $user = $this->userFor($this->companyA, User::ROLE_SENIOR);

        $user->forceFill(array_merge([
            'auto_assign_enabled' => true,
            'is_active'           => true,
        ], $attributes))->save();

        Company::flushAdvisorCache();

        return $user;
    }

    private function pick(): ?string
    {
        return app(AdvisorAssignmentService::class)->pickFor((int) $this->companyB->id);
    }

    // ── Seçim işe yarıyor mu ────────────────────────────────────────────────

    public function test_chosen_advisor_wins_over_automatic_distribution(): void
    {
        $this->linkPartner();

        $first  = $this->advisor();   // otomatik dagitimda once gelir (id sirasi)
        $chosen = $this->advisor();

        $this->companyB->update(['default_advisor_email' => $chosen->email]);

        $this->assertSame($chosen->email, $this->pick(), 'Ust firmanin secimi uygulanmadi.');
        $this->assertNotSame($first->email, $this->pick());
    }

    /** Seçim yoksa eski davranış: en az yüklü danışman. */
    public function test_without_a_choice_the_least_loaded_wins(): void
    {
        $this->linkPartner();

        $busy = $this->advisor();
        $free = $this->advisor();

        StudentAssignment::create([
            'company_id'   => $this->companyB->id,
            'student_id'   => 'STU-YUK',
            'senior_email' => $busy->email,
            'is_archived'  => false,
        ]);

        $this->assertSame($free->email, $this->pick());
    }

    // ── Sınırlar ────────────────────────────────────────────────────────────

    /**
     * KAPASİTE KURALI DELİNMEZ.
     *
     * Seçilen danışman doluysa sistem otomatik dağıtıma döner; aksi halde
     * "üst firma seçti" diye kapasitesi aşılırdı.
     */
    public function test_choice_is_ignored_when_the_advisor_is_full(): void
    {
        $this->linkPartner();

        $chosen = $this->advisor(['max_capacity' => 1]);
        $other  = $this->advisor();

        StudentAssignment::create([
            'company_id'   => $this->companyB->id,
            'student_id'   => 'STU-DOLU',
            'senior_email' => $chosen->email,
            'is_archived'  => false,
        ]);

        $this->companyB->update(['default_advisor_email' => $chosen->email]);

        $this->assertSame($other->email, $this->pick(), 'Dolu danismana atama yapildi.');
    }

    /** Pasif danışman seçilmiş olsa bile atanmaz. */
    public function test_choice_is_ignored_when_the_advisor_is_inactive(): void
    {
        $this->linkPartner();

        $chosen = $this->advisor();
        $other  = $this->advisor();

        $this->companyB->update(['default_advisor_email' => $chosen->email]);

        $chosen->forceFill(['is_active' => false])->save();
        Company::flushAdvisorCache();

        $this->assertSame($other->email, $this->pick());
    }

    /** Otomatik atamaya kapalı danışman seçilmiş olsa bile atanmaz. */
    public function test_choice_is_ignored_when_auto_assign_is_off(): void
    {
        $this->linkPartner();

        $chosen = $this->advisor(['auto_assign_enabled' => false]);
        $other  = $this->advisor();

        $this->companyB->update(['default_advisor_email' => $chosen->email]);

        $this->assertSame($other->email, $this->pick());
    }

    /** Havuzda hiç danışman yoksa null döner — kırılmaz. */
    public function test_no_advisor_returns_null(): void
    {
        $this->linkPartner();

        $this->companyB->update(['default_advisor_email' => 'olmayan@example.test']);

        $this->assertNull($this->pick());
    }
}
