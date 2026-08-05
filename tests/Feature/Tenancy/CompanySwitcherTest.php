<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\SystemEventLog;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Şirket bağlamı değiştirici.
 *
 * NEDEN VAR: MentorDE partner firmaların süreçlerini yürütüyor. Personel
 * MentorDE bağlamındayken partner öğrencisi için kayıt üretirse o kayıt
 * MENTORDE'nin kutusuna yazılır ve partner firma kendi öğrencisinin geçmişini
 * göremez. Bağlam değişince yazma hedefi de partnere geçer.
 *
 * Yetkiyi permission değil GÖRÜNÜR KÜME belirler: kişi zaten göremediği
 * şirkete geçemez.
 */
class CompanySwitcherTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private const URL = '/company-context/switch';

    /** companyA = üst firma, companyB = altındaki partner. */
    private function makeHierarchy(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();
    }

    // ── Yazma hedefi gerçekten değişiyor mu ─────────────────────────────────

    /**
     * ASIL MESELE: bağlam değiştikten sonra üretilen kayıt PARTNERİN kutusuna
     * düşmeli. Düşmezse partner firma kendi öğrencisinin geçmişini göremez.
     */
    public function test_records_created_after_switching_belong_to_the_target_company(): void
    {
        $this->makeHierarchy();
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->actingAs($manager)
            ->post(self::URL, ['company_id' => $this->companyB->id])
            ->assertRedirect();

        // Sonraki istek partnerin bağlamında açılmalı
        $this->actingAs($manager)->get('/manager/dashboard');

        $this->assertSame(
            (int) $this->companyB->id,
            (int) TenantContext::writeId(),
            'Baglam degistikten sonra yazma hedefi partnere gecmedi.'
        );

        $log = SystemEventLog::create([
            'event_type' => 'switcher.test',
            'message' => 'partner baglaminda uretildi',
        ]);

        $this->assertSame((int) $this->companyB->id, (int) $log->company_id);
    }

    public function test_switching_back_restores_the_own_company(): void
    {
        $this->makeHierarchy();
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->actingAs($manager)->post(self::URL, ['company_id' => $this->companyB->id]);
        $this->actingAs($manager)->post(self::URL, ['company_id' => $this->companyA->id]);
        $this->actingAs($manager)->get('/manager/dashboard');

        $this->assertSame((int) $this->companyA->id, (int) TenantContext::writeId());
    }

    // ── Yetki ───────────────────────────────────────────────────────────────

    /** Görünür kümesinde olmayan şirkete geçilemez. */
    public function test_cannot_switch_to_a_company_outside_the_visible_set(): void
    {
        // Hiyerarşi YOK — companyB companyA'nın altında değil
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->actingAs($manager)
            ->post(self::URL, ['company_id' => $this->companyB->id])
            ->assertSessionHasErrors('company_id');

        $this->actingAs($manager)->get('/manager/dashboard');

        $this->assertNotSame(
            (int) $this->companyB->id,
            (int) TenantContext::writeId(),
            'Yetkisiz sirkete gecildi.'
        );
    }

    /** Alt firma üst firmaya GEÇEMEZ — izolasyon yatay ve tek yönlü. */
    public function test_partner_cannot_switch_into_the_parent(): void
    {
        $this->makeHierarchy();
        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->actingAs($partnerManager)
            ->post(self::URL, ['company_id' => $this->companyA->id])
            ->assertSessionHasErrors('company_id');
    }

    /** Öğrenci rolü alt firma kümesine hiç girmez, dolayısıyla geçemez. */
    public function test_student_cannot_switch(): void
    {
        $this->makeHierarchy();
        $student = $this->userFor($this->companyA, User::ROLE_STUDENT);

        $this->actingAs($student)
            ->post(self::URL, ['company_id' => $this->companyB->id])
            ->assertSessionHasErrors('company_id');
    }

    public function test_cannot_switch_into_a_suspended_company(): void
    {
        $this->makeHierarchy();
        $this->companyB->update(['is_active' => false]);

        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->actingAs($manager)
            ->post(self::URL, ['company_id' => $this->companyB->id])
            ->assertSessionHasErrors('company_id');
    }

    public function test_guests_cannot_switch(): void
    {
        $response = $this->post(self::URL, ['company_id' => $this->companyA->id]);

        $this->assertContains($response->getStatusCode(), [401, 403, 404, 302]);
    }

    // ── Görünürlük ──────────────────────────────────────────────────────────

    /**
     * Seçiciyi doğrudan render et.
     *
     * `/manager/dashboard` üzerinden ölçmek işe yaramıyor: `require.2fa`
     * middleware'i 2FA kurulumuna yönlendiriyor ve yönlendirme sayfasında
     * aranan metin doğal olarak bulunmuyor — "gizli" testi SAHTE geçerdi.
     */
    private function renderSwitcher(User $user): string
    {
        $this->actingAs($user);

        TenantContext::bind((int) $user->company_id, $user->visibleCompanyIds());

        return view('partials.company-switcher')->render();
    }

    /** Tek şirketli kullanıcıya seçici GÖSTERİLMEZ — gereksiz gürültü. */
    public function test_switcher_is_hidden_for_single_company_users(): void
    {
        $partnerManager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->assertStringNotContainsString(
            'data-company-switcher',
            $this->renderSwitcher($partnerManager)
        );
    }

    public function test_switcher_is_shown_to_multi_company_staff(): void
    {
        $this->makeHierarchy();
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $html = $this->renderSwitcher($manager);

        $this->assertStringContainsString('data-company-switcher', $html);
        $this->assertStringContainsString($this->companyB->name, $html);
        $this->assertStringContainsString($this->companyA->name, $html);
    }

    /** Öğrenciye seçici gösterilmez — alt firma kümesine hiç girmiyor. */
    public function test_switcher_is_hidden_for_students(): void
    {
        $this->makeHierarchy();
        $student = $this->userFor($this->companyA, User::ROLE_STUDENT);

        $this->assertStringNotContainsString(
            'data-company-switcher',
            $this->renderSwitcher($student)
        );
    }
}
