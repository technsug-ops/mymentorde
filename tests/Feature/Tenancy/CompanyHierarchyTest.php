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
 * Şirket hiyerarşisi — üst firma alt firmanın verisini görür.
 *
 *     DGmarkt            SaaS saglayici (platform sahibi, kisitsiz)
 *       └── MentorDE     operasyonu yuruten firma
 *             └── Aythink, ...   B2B partner firmalar
 *
 * MentorDE partner firmalarin ogrencilerinin surecini yurutuyor; lead'i
 * goremezse isi yapamaz. Izolasyon YATAY'dir:
 *
 *   • Firma firmayi goremez
 *   • Alt firma UST firmayi goremez
 *   • Ogrenci / aday / bayi rolleri alt firma verisine ASLA erisemez
 */
class CompanyHierarchyTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /** companyA = üst firma (MentorDE rolünde), companyB = altındaki partner. */
    private function makeHierarchy(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();
    }

    private function leadIn(Company $company, string $email): GuestApplication
    {
        return TenantContext::runFor((int) $company->id, fn (): GuestApplication => GuestApplication::create([
            'tracking_token' => 'tok-' . uniqid(),
            'first_name' => 'Aday',
            'last_name' => 'Test',
            'email' => $email,
            'application_type' => 'bachelor',
        ]));
    }

    /** Kullanıcının görünür kümesiyle sorgu çalıştır (middleware'in yaptığı). */
    private function asUser(User $user, callable $callback): mixed
    {
        $previous = TenantContext::snapshot();

        try {
            TenantContext::bind((int) $user->company_id, $user->visibleCompanyIds());

            return $callback();
        } finally {
            TenantContext::restore($previous);
        }
    }

    // ── Yukarıdan aşağı görüş ───────────────────────────────────────────────

    public function test_parent_company_staff_sees_child_company_leads(): void
    {
        $this->makeHierarchy();
        $this->leadIn($this->companyB, 'partner-adayi@example.test');

        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $seen = $this->asUser($manager, fn () => GuestApplication::query()
            ->where('email', 'partner-adayi@example.test')->first());

        $this->assertNotNull($seen, 'Ust firma personeli alt firmanin adayini goremiyor.');
    }

    public function test_parent_staff_still_sees_its_own_leads(): void
    {
        $this->makeHierarchy();
        $this->leadIn($this->companyA, 'kendi-adayim@example.test');

        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $seen = $this->asUser($manager, fn () => GuestApplication::query()
            ->where('email', 'kendi-adayim@example.test')->first());

        $this->assertNotNull($seen);
    }

    // ── Aşağıdan yukarı KAPALI ──────────────────────────────────────────────

    public function test_child_company_cannot_see_the_parent(): void
    {
        $this->makeHierarchy();
        $this->leadIn($this->companyA, 'ust-firma-adayi@example.test');

        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $seen = $this->asUser($partnerManager, fn () => GuestApplication::query()
            ->where('email', 'ust-firma-adayi@example.test')->first());

        $this->assertNull($seen, 'Alt firma ust firmanin verisini gordu — izolasyon kirik.');
    }

    public function test_sibling_companies_cannot_see_each_other(): void
    {
        $this->makeHierarchy();

        $sibling = Company::create([
            'name' => 'Kardes Firma',
            'code' => 'kardes',
            'is_active' => true,
            'parent_company_id' => $this->companyA->id,
        ]);
        Company::flushHierarchyCache();

        $this->leadIn($this->companyB, 'kardes-gormesin@example.test');

        $siblingManager = $this->userFor($sibling, User::ROLE_MANAGER);

        $seen = $this->asUser($siblingManager, fn () => GuestApplication::query()
            ->where('email', 'kardes-gormesin@example.test')->first());

        $this->assertNull($seen, 'Kardes firmalar birbirini gordu.');
    }

    // ── ROL SINIRI — en kritik kısım ────────────────────────────────────────

    /**
     * Üst firmaya kayıtlı bir ÖĞRENCİ partner verisini görmemeli.
     *
     * Erişim şirkete değil ROLE bağlı. Aksi halde MentorDE'ye kayıtlı her
     * öğrenci tüm partner firmaların adaylarını görürdü.
     */
    public function test_student_of_parent_company_cannot_see_child_data(): void
    {
        $this->makeHierarchy();
        $this->leadIn($this->companyB, 'ogrenci-gormesin@example.test');

        $student = $this->userFor($this->companyA, User::ROLE_STUDENT);

        $this->assertSame(
            [(int) $this->companyA->id],
            $student->visibleCompanyIds(),
            'Ogrenci rolune alt firma erisimi verildi.'
        );

        $seen = $this->asUser($student, fn () => GuestApplication::query()
            ->where('email', 'ogrenci-gormesin@example.test')->first());

        $this->assertNull($seen);
    }

    public function test_dealer_of_parent_company_cannot_see_child_data(): void
    {
        $this->makeHierarchy();
        $this->leadIn($this->companyB, 'bayi-gormesin@example.test');

        $dealer = $this->userFor($this->companyA, User::ROLE_DEALER);

        $seen = $this->asUser($dealer, fn () => GuestApplication::query()
            ->where('email', 'bayi-gormesin@example.test')->first());

        $this->assertNull($seen, 'Bayi partner firma verisini gordu.');
    }

    public function test_guest_of_parent_company_cannot_see_child_data(): void
    {
        $this->makeHierarchy();
        $this->leadIn($this->companyB, 'aday-gormesin@example.test');

        $guest = $this->userFor($this->companyA, User::ROLE_GUEST);

        $seen = $this->asUser($guest, fn () => GuestApplication::query()
            ->where('email', 'aday-gormesin@example.test')->first());

        $this->assertNull($seen);
    }

    // ── Yazma hedefi tek kalır ──────────────────────────────────────────────

    /**
     * Görünürlük genişledi ama YAZMA hâlâ tek şirkete gider.
     * Üst firma personeli kayıt oluşturursa kendi şirketine yazar.
     */
    public function test_write_target_stays_the_users_own_company(): void
    {
        $this->makeHierarchy();

        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $created = $this->asUser($manager, fn (): GuestApplication => GuestApplication::create([
            'tracking_token' => 'tok-write-' . uniqid(),
            'first_name' => 'Yeni',
            'last_name' => 'Kayit',
            'email' => 'yazma-hedefi@example.test',
            'application_type' => 'bachelor',
        ]));

        $this->assertSame((int) $this->companyA->id, (int) $created->company_id);
    }

    // ── Derin hiyerarşi ve döngü koruması ───────────────────────────────────

    public function test_descendants_include_grandchildren(): void
    {
        $this->makeHierarchy();

        $grandchild = Company::create([
            'name' => 'Torun Firma',
            'code' => 'torun',
            'is_active' => true,
            'parent_company_id' => $this->companyB->id,
        ]);
        Company::flushHierarchyCache();

        $ids = Company::descendantIds((int) $this->companyA->id);

        $this->assertContains((int) $this->companyB->id, $ids);
        $this->assertContains((int) $grandchild->id, $ids);
    }

    /** Veri bozulursa (A→B→A) görünürlük hesabı sonsuz döngüye girmemeli. */
    public function test_cyclic_hierarchy_does_not_hang(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        $this->companyA->update(['parent_company_id' => $this->companyB->id]);
        Company::flushHierarchyCache();

        $ids = Company::descendantIds((int) $this->companyA->id);

        $this->assertContains((int) $this->companyB->id, $ids);
        $this->assertNotContains((int) $this->companyA->id, $ids, 'Sirket kendi torunu olarak dondu.');
    }

    // ── Panel ───────────────────────────────────────────────────────────────

    public function test_owner_can_set_the_parent_company(): void
    {
        $owner = $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);

        $this->actingAs($owner)
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'parent_company_id' => $this->companyA->id,
            ])
            ->assertRedirect();

        $this->assertSame((int) $this->companyA->id, (int) $this->companyB->fresh()->parent_company_id);
    }

    /**
     * Ağaçtaki yer platform sahibinin seçimi.
     *
     * Yeni firma ana şirketin altına da takılabilir, bir PARTNER firmanın altına
     * da — örn. FF ile anlaşıp onun altına kendi bayi ağacını kurmak.
     */
    public function test_new_company_can_be_placed_under_any_company(): void
    {
        $owner = $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);

        $this->actingAs($owner)
            ->post('/platform/companies', [
                'name' => 'FF Alt Bayi',
                'code' => 'ff_alt_bayi',
                'subscription_tier' => Company::TIER_BASIC,
                'admin_name' => 'FF Yonetici',
                'admin_email' => 'yonetici@ff-alt.test',
                'admin_password' => 'gizli-sifre-123',
                'parent_company_id' => $this->companyB->id,
            ])
            ->assertRedirect();

        $created = Company::query()->where('code', 'ff_alt_bayi')->first();

        $this->assertNotNull($created);
        $this->assertSame((int) $this->companyB->id, (int) $created->parent_company_id);
    }

    /** Üst firma boş bırakılırsa bağımsız tenant olur — sessizce ana şirkete bağlanmaz. */
    public function test_new_company_without_parent_stays_independent(): void
    {
        $owner = $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);

        $this->actingAs($owner)
            ->post('/platform/companies', [
                'name' => 'Bagimsiz Firma',
                'code' => 'bagimsiz',
                'subscription_tier' => Company::TIER_BASIC,
                'admin_name' => 'Bagimsiz Yonetici',
                'admin_email' => 'yonetici@bagimsiz.test',
                'admin_password' => 'gizli-sifre-123',
            ])
            ->assertRedirect();

        $this->assertNull(Company::query()->where('code', 'bagimsiz')->first()?->parent_company_id);
    }

    public function test_company_cannot_become_its_own_parent(): void
    {
        $owner = $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);

        $this->actingAs($owner)
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'parent_company_id' => $this->companyB->id,
            ])
            ->assertSessionHasErrors('parent_company_id');

        $this->assertNull($this->companyB->fresh()->parent_company_id);
    }

    public function test_parent_cannot_be_set_to_its_own_descendant(): void
    {
        $this->makeHierarchy(); // B, A'nın altında

        $owner = $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);

        // A'yı B'nin altına almak döngü yaratır
        $this->actingAs($owner)
            ->post('/platform/companies/' . $this->companyA->id . '/branding', [
                'parent_company_id' => $this->companyB->id,
            ])
            ->assertSessionHasErrors('parent_company_id');

        $this->assertNull($this->companyA->fresh()->parent_company_id);
    }
}
