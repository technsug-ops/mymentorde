<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use App\Services\AdvisorAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Danışman uzmanlık etiketleri — bir kişide birden fazla olabilir.
 *
 * Bachelor / Master / Ausbildung / Vize. Otomatik atama, adayın başvuru
 * türüyle eşleşen danışmanı seçer.
 *
 * ⚠ ETİKETSİZ = GENEL. Hiç etiketi olmayan danışman her başvuruya uygun
 * sayılır; aksi halde etiketleme başlar başlamaz etiketlenmemiş herkes
 * havuzdan sessizce düşerdi.
 */
class AdvisorSpecialtyTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function linkPartner(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();
        Company::flushAdvisorCache();
    }

    /** @param list<string> $specialties */
    private function advisor(array $specialties = [], array $extra = []): User
    {
        $user = $this->userFor($this->companyA, User::ROLE_SENIOR);

        $user->forceFill(array_merge([
            'auto_assign_enabled' => true,
            'is_active'           => true,
            'advisor_specialties' => $specialties !== [] ? $specialties : null,
        ], $extra))->save();

        Company::flushAdvisorCache();

        return $user;
    }

    private function pick(string $type): ?string
    {
        return app(AdvisorAssignmentService::class)->pickFor((int) $this->companyB->id, $type);
    }

    // ── Eşleşme ─────────────────────────────────────────────────────────────

    public function test_application_type_goes_to_the_matching_advisor(): void
    {
        $this->linkPartner();

        $bachelor = $this->advisor(['bachelor']);
        $master   = $this->advisor(['master']);

        $this->assertSame($bachelor->email, $this->pick('bachelor'));
        $this->assertSame($master->email, $this->pick('master'));
    }

    /** Bir danışman birden fazla alanda uzman olabilir. */
    public function test_multiple_specialties_on_one_advisor(): void
    {
        $this->linkPartner();

        $both = $this->advisor(['bachelor', 'master']);
        $this->advisor(['ausbildung']);

        $this->assertSame($both->email, $this->pick('bachelor'));
        $this->assertSame($both->email, $this->pick('master'));
    }

    /**
     * Etiketsiz danışman GENELDİR.
     *
     * Aksi halde ilk etiketleme yapıldığı anda etiketlenmemiş herkes havuz
     * dışında kalır ve atamalar sessizce tek kişiye yığılırdı.
     */
    public function test_untagged_advisor_matches_everything(): void
    {
        $this->linkPartner();

        $general = $this->advisor();

        $this->assertSame($general->email, $this->pick('bachelor'));
        $this->assertSame($general->email, $this->pick('ausbildung'));
    }

    /** Eşleşen yoksa havuzun tamamına düşülür — aday danışmansız kalmaz. */
    public function test_falls_back_to_the_whole_pool_when_nothing_matches(): void
    {
        $this->linkPartner();

        $onlyMaster = $this->advisor(['master']);

        $this->assertSame($onlyMaster->email, $this->pick('ausbildung'));
    }

    // ── Temizlik ────────────────────────────────────────────────────────────

    /** Bilinmeyen etiket yok sayılır — veri bozulsa bile eşleşme şaşmaz. */
    public function test_unknown_tags_are_ignored(): void
    {
        $this->linkPartner();

        $advisor = $this->advisor(['bachelor', 'uydurma-etiket']);

        $this->assertSame(['bachelor'], $advisor->fresh()->advisorSpecialties());
    }

    // ── Panel ───────────────────────────────────────────────────────────────

    /**
     * ⚠ Danışmanlar `/manager/staff` ekranında YÖNETİLMİYOR — orası sistem,
     * operasyon, finans, pazarlama ve satış rolleri için. Etiket formunun
     * yeri danışman detay sayfası.
     */
    public function test_manager_can_set_specialties(): void
    {
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);
        $advisor = $this->userFor($this->companyA, User::ROLE_SENIOR);

        $this->actingAs($manager)->withSession(['2fa_passed' => true])
            ->post('/manager/seniors/' . urlencode($advisor->email) . '/specialties', [
                'specialties' => ['master', 'vize'],
            ])
            ->assertRedirect();

        $this->assertSame(['master', 'vize'], $advisor->fresh()->advisorSpecialties());
    }

    /** Hepsi kaldırılabilmeli — danışman yeniden "genel" olur. */
    public function test_specialties_can_be_cleared(): void
    {
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);
        $advisor = $this->advisor(['master']);

        $this->actingAs($manager)->withSession(['2fa_passed' => true])
            ->post('/manager/seniors/' . urlencode($advisor->email) . '/specialties', [
                'specialties' => [''],
            ])
            ->assertRedirect();

        $this->assertSame([], $advisor->fresh()->advisorSpecialties());
    }

    /** Danışman olmayan bir hesaba etiket yazılamaz. */
    public function test_non_advisor_cannot_be_tagged(): void
    {
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);
        $other   = $this->userFor($this->companyA, User::ROLE_OPERATIONS_STAFF);

        $this->actingAs($manager)->withSession(['2fa_passed' => true])
            ->post('/manager/seniors/' . urlencode($other->email) . '/specialties', [
                'specialties' => ['master'],
            ])
            ->assertNotFound();
    }
}
