<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Şirket bağlamı değiştirme güvenliği (Faz 0).
 *
 * `current_company_id` session değeri, kullanıcının hangi şirketin verisini
 * gördüğünü belirler. Bu yüzden şirket değiştirme uç noktası kullanıcının o
 * şirkete erişim hakkını doğrulamak zorundadır — rol/permission kontrolü tek
 * başına yetmez, çünkü aynı yetki her şirkette bulunur.
 *
 * Regresyon: Bu kontrol eklenmeden önce B firmasının marketing_admin'i
 * dropdown'dan A'yı seçip A'nın tüm pazarlama verisini okuyup yazabiliyordu.
 */
class CompanyContextSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::create(['name' => 'Firma A', 'code' => 'firma_a', 'is_active' => true]);
        $this->companyB = Company::create(['name' => 'Firma B', 'code' => 'firma_b', 'is_active' => true]);
    }

    private function makeUser(string $role, ?Company $company): User
    {
        return User::create([
            'name' => 'Test ' . $role,
            'email' => $role . '-' . ($company?->code ?? 'none') . '-' . uniqid() . '@example.test',
            'password' => Hash::make('secret-password'),
            'role' => $role,
            'company_id' => $company?->id,
        ]);
    }

    public function test_user_cannot_switch_to_a_company_they_do_not_belong_to(): void
    {
        $userOfA = $this->makeUser(User::ROLE_MARKETING_ADMIN, $this->companyA);

        $response = $this->actingAs($userOfA)
            ->postJson('/api/v1/marketing-admin/companies/switch', [
                'company_id' => $this->companyB->id,
            ]);

        $response->assertForbidden();

        // Session bağlamı DEĞİŞMEMELİ — reddedilen istek yan etki bırakmamalı.
        $this->assertNotSame(
            (int) $this->companyB->id,
            (int) session('current_company_id', 0),
            'Reddedilen switch isteği session bağlamını değiştirdi.'
        );
    }

    public function test_user_can_switch_to_their_own_company(): void
    {
        $userOfA = $this->makeUser(User::ROLE_MARKETING_ADMIN, $this->companyA);

        $this->actingAs($userOfA)
            ->postJson('/api/v1/marketing-admin/companies/switch', [
                'company_id' => $this->companyA->id,
            ])
            ->assertOk()
            ->assertJsonPath('current_company_id', $this->companyA->id);
    }

    /**
     * Platform sahibi kısıtsızdır — her şirketin bağlamına geçebilir.
     *
     * NOT: `platform_owner` rolü User::MARKETING_ACCESS_ROLES içinde DEĞİL (platform
     * sahibi pazarlama panelini değil /platform/* panelini kullanır). Burada test
     * edilen şey o panel erişimi değil, controller'ın yetki mantığı — bu yüzden
     * marketing kapısı devre dışı bırakılıyor.
     */
    public function test_platform_owner_can_switch_to_any_company(): void
    {
        $owner = $this->makeUser(User::ROLE_PLATFORM_OWNER, $this->companyA);

        $this->actingAs($owner)
            ->withoutMiddleware(\App\Http\Middleware\EnsureMarketingAccess::class)
            ->postJson('/api/v1/marketing-admin/companies/switch', [
                'company_id' => $this->companyB->id,
            ])
            ->assertOk()
            ->assertJsonPath('current_company_id', $this->companyB->id);
    }

    public function test_company_list_only_shows_companies_the_user_can_access(): void
    {
        $userOfA = $this->makeUser(User::ROLE_MARKETING_ADMIN, $this->companyA);

        $response = $this->actingAs($userOfA)->getJson('/api/v1/marketing-admin/companies');

        $response->assertOk();

        $codes = array_column($response->json('companies'), 'code');

        $this->assertContains('firma_a', $codes);
        $this->assertNotContains('firma_b', $codes, 'Kullanıcı başka şirketin varlığını görebiliyor.');
    }

    public function test_platform_owner_sees_all_companies(): void
    {
        $owner = $this->makeUser(User::ROLE_PLATFORM_OWNER, $this->companyA);

        $response = $this->actingAs($owner)
            ->withoutMiddleware(\App\Http\Middleware\EnsureMarketingAccess::class)
            ->getJson('/api/v1/marketing-admin/companies');

        $response->assertOk();

        $codes = array_column($response->json('companies'), 'code');

        $this->assertContains('firma_a', $codes);
        $this->assertContains('firma_b', $codes);
    }

    public function test_creating_a_company_is_restricted_to_platform_owner(): void
    {
        $manager = $this->makeUser(User::ROLE_MANAGER, $this->companyA);

        $this->actingAs($manager)
            ->withoutMiddleware(\App\Http\Middleware\EnsureMarketingAccess::class)
            ->postJson('/api/v1/config/companies', [
                'name' => 'Kacak Sirket',
                'code' => 'kacak',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('companies', ['code' => 'kacak']);
    }
}
