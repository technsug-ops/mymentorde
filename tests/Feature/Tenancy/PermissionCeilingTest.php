<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use App\Support\PermissionCeiling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Şirket yetki tavanı — üst firmanın alt firmaya koyduğu kısıt.
 *
 * İş modeli: partner firmalar öğrenciyi bize devreder, operasyonu biz
 * yürütürüz. Ama her firma aynı değil; hangi firmanın ne kadar yetkisi
 * olacağını ağacın üstündeki firma belirler.
 *
 * MODEL: rol yetkiyi VERİR, tavan DARALTIR. Varsayılan tam yetki.
 */
class PermissionCeilingTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function owner(): User
    {
        return $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
    }

    private function primaryCompany(): Company
    {
        return Company::query()->whereRaw('lower(code) = ?', ['mentorde'])->firstOrFail();
    }

    private function deny(Company $company, array $codes): void
    {
        $company->update(['denied_permission_codes' => $codes]);
        Company::flushHierarchyCache();
    }

    // ── Temel davranış ──────────────────────────────────────────────────────

    /** Kısıt yoksa rolün verdiği her şey geçerli. */
    public function test_manager_has_full_role_permissions_by_default(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->assertTrue($manager->hasPermissionCode('student.assignment.manage'));
        $this->assertTrue($manager->hasPermissionCode('revenue.manage'));
    }

    public function test_denied_permission_is_removed_from_the_manager(): void
    {
        $this->deny($this->companyB, ['student.assignment.manage']);

        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->assertFalse(
            $manager->hasPermissionCode('student.assignment.manage'),
            'Kisitlanan yetki hala duruyor — operasyon partnere acik kaldi.'
        );
        // Diğerleri etkilenmemeli
        $this->assertTrue($manager->hasPermissionCode('revenue.manage'));
    }

    /** Kısıt ROLDEN bağımsız: senior da aynı kısıta tabi. */
    public function test_ceiling_applies_to_every_role_in_the_company(): void
    {
        $this->deny($this->companyB, ['doc_request.use']);

        $senior = $this->userFor($this->companyB, User::ROLE_SENIOR);
        $senior->can_request_documents = true;
        $senior->save();

        $this->assertFalse(
            $senior->fresh()->hasPermissionCode('doc_request.use'),
            'Kisiye ozel yetki sirket tavanini deldi.'
        );
    }

    // ── Ağaçtan aşağı birikme ───────────────────────────────────────────────

    /** Üst firmaya konan kısıt alt firmayı da bağlar. */
    public function test_restriction_cascades_to_child_companies(): void
    {
        $child = Company::create([
            'name' => 'Alt Firma',
            'code' => 'alt_firma',
            'is_active' => true,
            'parent_company_id' => $this->companyB->id,
        ]);

        $this->deny($this->companyB, ['revenue.manage']);

        $childManager = $this->userFor($child, User::ROLE_MANAGER);

        $this->assertFalse(
            $childManager->hasPermissionCode('revenue.manage'),
            'Ust firmanin kisiti alt firmaya inmedi.'
        );
    }

    public function test_effective_denials_merge_own_and_inherited(): void
    {
        $child = Company::create([
            'name' => 'Alt Firma',
            'code' => 'alt_firma',
            'is_active' => true,
            'parent_company_id' => $this->companyB->id,
        ]);

        $this->companyB->update(['denied_permission_codes' => ['revenue.manage']]);
        $child->update(['denied_permission_codes' => ['dam.delete']]);
        Company::flushHierarchyCache();

        $effective = Company::effectiveDeniedPermissions((int) $child->id);

        $this->assertEqualsCanonicalizing(['revenue.manage', 'dam.delete'], $effective);
    }

    /** Kardeş firma etkilenmez. */
    public function test_sibling_company_is_not_affected(): void
    {
        $this->deny($this->companyB, ['student.assignment.manage']);

        $siblingManager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->assertTrue($siblingManager->hasPermissionCode('student.assignment.manage'));
    }

    // ── Muafiyet ve koruma ──────────────────────────────────────────────────

    /**
     * Platform sahibi tavandan muaf — kendi platformunu kilitleyemesin.
     *
     * Sahibin ROLE_DEFAULT_PERMISSION_CODES'ta karşılığı yok (platform.owner
     * middleware'iyle korunuyor), o yüzden muafiyeti ölçmek için kişiye özel
     * `can_request_documents` yetkisi kullanılıyor: aynı yetki manager'da
     * kısıtla silinirken sahipte kalmalı.
     */
    public function test_platform_owner_is_exempt(): void
    {
        $this->deny($this->companyA, ['doc_request.use']);

        $owner = $this->owner();
        $owner->can_request_documents = true;
        $owner->save();

        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);
        $manager->can_request_documents = true;
        $manager->save();

        $this->assertTrue(
            $owner->fresh()->hasPermissionCode('doc_request.use'),
            'Platform sahibi kendi koydugu kisitla kilitlendi.'
        );
        $this->assertFalse(
            $manager->fresh()->hasPermissionCode('doc_request.use'),
            'Kisit manager uzerinde islemedi — test anlamsiz.'
        );
    }

    public function test_primary_company_cannot_be_restricted(): void
    {
        $primary = $this->primaryCompany();

        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $primary->id . '/permissions', [
                'denied_permission_codes' => ['config.manage'],
            ])
            ->assertSessionHasErrors('denied_permission_codes');

        $this->assertNull($primary->fresh()->denied_permission_codes);
    }

    /** Bilinmeyen kod kabul edilmemeli — yazım hatası sessizce kaydolmasın. */
    public function test_unknown_permission_codes_are_discarded(): void
    {
        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/permissions', [
                'denied_permission_codes' => ['revenue.manage', 'uydurma.kod', ''],
            ])
            ->assertRedirect();

        $this->assertSame(['revenue.manage'], $this->companyB->fresh()->denied_permission_codes);
    }

    // ── Panel ───────────────────────────────────────────────────────────────

    public function test_owner_can_set_and_clear_the_ceiling(): void
    {
        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/permissions', [
                'denied_permission_codes' => ['student.assignment.manage', 'revenue.manage'],
            ])
            ->assertRedirect();

        $this->assertEqualsCanonicalizing(
            ['student.assignment.manage', 'revenue.manage'],
            $this->companyB->fresh()->denied_permission_codes
        );

        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/permissions', [
                'denied_permission_codes' => [''],
            ])
            ->assertRedirect();

        $this->assertNull($this->companyB->fresh()->denied_permission_codes);
    }

    public function test_company_users_cannot_change_the_ceiling(): void
    {
        $firmUser = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $response = $this->actingAs($firmUser)
            ->post('/platform/companies/' . $this->companyB->id . '/permissions', [
                'denied_permission_codes' => [],
            ]);

        $this->assertContains($response->getStatusCode(), [403, 404, 302]);
    }

    public function test_company_detail_page_renders_the_ceiling_form(): void
    {
        $this->deny($this->companyB, ['revenue.manage']);

        $this->actingAs($this->owner())
            ->get('/platform/companies/' . $this->companyB->id)
            ->assertOk()
            ->assertSee('Yetki Kısıtları', false)
            ->assertSee(PermissionCeiling::RESTRICTABLE['revenue.manage']['label'], false);
    }
}
