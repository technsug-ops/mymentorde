<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Üst firmanın kendi alt firmalarını yönetmesi.
 *
 * Yetki tavanını şimdiye kadar yalnızca platform sahibi ayarlayabiliyordu.
 * Oysa kısıtı koyması gereken taraf ağacın üstündeki FİRMA — partnerle
 * anlaşmayı o yapıyor, sınırı o biliyor.
 */
class PartnerCompanyManagementTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /** Manager rotaları `require.2fa` arkasında. */
    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    /** companyB, companyA'nın altına alınır. */
    private function makeHierarchy(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();
    }

    // ── Yönetebilme ─────────────────────────────────────────────────────────

    public function test_parent_manager_sees_its_child_companies(): void
    {
        $this->makeHierarchy();
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->get('/manager/partners')
            ->assertOk()
            ->assertSee($this->companyB->name, false);
    }

    public function test_parent_manager_can_restrict_a_child(): void
    {
        $this->makeHierarchy();
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->post('/manager/partners/' . $this->companyB->id, [
                'denied_permission_codes' => ['revenue.manage'],
            ])
            ->assertRedirect();

        $this->assertSame(['revenue.manage'], $this->companyB->fresh()->denied_permission_codes);
    }

    /** Kısıt gerçekten uygulanmalı — kayıt yeterli değil. */
    public function test_restriction_takes_effect_on_the_child_users(): void
    {
        $this->makeHierarchy();
        $parentManager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->asStaff($parentManager)->post('/manager/partners/' . $this->companyB->id, [
            'denied_permission_codes' => ['student.assignment.manage'],
        ]);

        $childManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->assertFalse($childManager->hasPermissionCode('student.assignment.manage'));
    }

    // ── Sınırlar ────────────────────────────────────────────────────────────

    /** Kendi şirketine kısıt koyamaz — kendini kilitlemesin. */
    public function test_manager_cannot_restrict_own_company(): void
    {
        $this->makeHierarchy();
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $response = $this->asStaff($manager)
            ->post('/manager/partners/' . $this->companyA->id, [
                'denied_permission_codes' => ['config.manage'],
            ]);

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $this->assertNull($this->companyA->fresh()->denied_permission_codes);
    }

    /** Altında olmayan firmaya dokunamaz. */
    public function test_manager_cannot_restrict_an_unrelated_company(): void
    {
        // Hiyerarşi YOK
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $response = $this->asStaff($manager)
            ->post('/manager/partners/' . $this->companyB->id, [
                'denied_permission_codes' => ['revenue.manage'],
            ]);

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $this->assertNull($this->companyB->fresh()->denied_permission_codes);
    }

    /** Alt firma üst firmayı yönetemez — izolasyon tek yönlü. */
    public function test_child_manager_cannot_manage_the_parent(): void
    {
        $this->makeHierarchy();
        $childManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $response = $this->asStaff($childManager)
            ->post('/manager/partners/' . $this->companyA->id, [
                'denied_permission_codes' => ['config.manage'],
            ]);

        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    /**
     * ÜSTTEN MİRAS gelen kısıt bu ekrandan KALDIRILAMAZ.
     * Platform sahibinin koyduğu sınırı ara kat gevşetememeli.
     */
    public function test_inherited_restriction_cannot_be_removed(): void
    {
        $this->makeHierarchy();

        // Üst firmaya (companyA) kısıt: alt firmaya miras kalır
        $this->companyA->update(['denied_permission_codes' => ['revenue.manage']]);
        Company::flushHierarchyCache();

        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        // Alt firmayı "kısıtsız" kaydetmeye çalış
        $this->asStaff($manager)->post('/manager/partners/' . $this->companyB->id, [
            'denied_permission_codes' => [''],
        ]);

        $childManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->assertFalse(
            $childManager->hasPermissionCode('revenue.manage'),
            'Miras kisit alt firmada gevsetildi.'
        );
    }

    // ── Yetki ───────────────────────────────────────────────────────────────

    /** `role.template.manage` yetkisi olmayan personel bu ekranı kullanamaz. */
    public function test_staff_without_the_permission_sees_nothing(): void
    {
        $this->makeHierarchy();
        $senior = $this->userFor($this->companyA, User::ROLE_SENIOR);

        $this->asStaff($senior)
            ->get('/manager/partners')
            ->assertDontSee($this->companyB->name, false);
    }

    public function test_students_cannot_reach_the_screen(): void
    {
        $this->makeHierarchy();
        $student = $this->userFor($this->companyA, User::ROLE_STUDENT);

        $response = $this->asStaff($student)->get('/manager/partners');

        $this->assertContains($response->getStatusCode(), [403, 404, 302]);
    }
}
