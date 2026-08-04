<?php

namespace Tests\Feature\Tenancy;

use App\Models\GuestApplication;
use App\Models\SystemEventLog;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * TENANT İZOLASYONU — kara kutu testi (Faz 3).
 *
 * Kullanıcının açık isteği: "3 firma panele girecek ve SADECE kendi öğrencilerini
 * görecek — birbirlerini kesinlikle görmemeliler."
 *
 * Burada doğrulanan davranışlar:
 *   • A'nın yöneticisi B'nin kullanıcılarını/adaylarını LİSTELEYEMEZ
 *   • Kimlik doğrulama şirketten BAĞIMSIZ çalışır (login kırılmamalı)
 *   • Yazma her zaman kullanıcının kendi şirketine gider
 *   • Çok-şirketli personel (company_user pivotu) birden fazla şirketi görebilir
 *   • Platform sahibi kısıtsızdır
 */
class TenantIsolationTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    // ── Kimlik doğrulama şirketten bağımsız olmalı ──────────────────────────

    /**
     * EN KRİTİK REGRESYON: User modeline şirket scope'u eklendiğinde login
     * kırılabilir — provider `where email = ...` yaparken scope devreye girip
     * başka şirketteki kullanıcıyı BULAMAZ. TenantAwareUserProvider bunu önler.
     */
    public function test_users_of_every_company_can_log_in(): void
    {
        foreach ([$this->companyA, $this->companyB] as $company) {
            $email = 'login-' . $company->code . '@example.test';

            User::create([
                'name' => 'Login Test',
                'email' => $email,
                'password' => Hash::make('secret-password'),
                'role' => User::ROLE_MANAGER,
                'company_id' => $company->id,
                'is_active' => true,
            ]);

            $this->post('/login', ['email' => $email, 'password' => 'secret-password'])
                ->assertRedirect();

            $this->assertAuthenticated(null, "'{$company->code}' şirketinin kullanıcısı giriş yapamadı.");
            $this->post('/logout');
        }
    }

    public function test_password_reset_finds_users_across_companies(): void
    {
        $email = 'reset-b@example.test';

        User::create([
            'name' => 'Reset Test',
            'email' => $email,
            'password' => Hash::make('secret-password'),
            'role' => User::ROLE_MANAGER,
            'company_id' => $this->companyB->id,
            'is_active' => true,
        ]);

        // A bağlamındayken B'nin kullanıcısı için sıfırlama istenebilmeli
        TenantContext::bind($this->companyA->id, [$this->companyA->id]);

        $this->post('/forgot-password', ['email' => $email])
            ->assertSessionHasNoErrors();
    }

    // ── Okuma izolasyonu ────────────────────────────────────────────────────

    public function test_user_queries_are_limited_to_the_visible_companies(): void
    {
        $this->userFor($this->companyA);
        $this->userFor($this->companyB);

        TenantContext::bind($this->companyA->id, [$this->companyA->id]);

        $emails = User::query()->pluck('company_id')->unique()->all();

        $this->assertSame(
            [(int) $this->companyA->id],
            array_map('intval', array_values($emails)),
            'User sorgusu başka şirketin kullanıcılarını döndürdü.'
        );
    }

    public function test_guest_applications_are_isolated(): void
    {
        $this->makeGuestFor($this->companyA, 'a@example.test');
        $this->makeGuestFor($this->companyB, 'b@example.test');

        TenantContext::bind($this->companyA->id, [$this->companyA->id]);

        $emails = GuestApplication::query()->pluck('email')->all();

        $this->assertContains('a@example.test', $emails);
        $this->assertNotContains('b@example.test', $emails, 'B şirketinin adayı A bağlamında göründü.');
    }

    // ── Yazma izolasyonu ────────────────────────────────────────────────────

    public function test_records_are_written_to_the_acting_users_company(): void
    {
        TenantContext::bind($this->companyB->id, [$this->companyB->id]);

        $log = SystemEventLog::create(['event_type' => 'x', 'message' => 'yazma']);

        $this->assertSame((int) $this->companyB->id, (int) $log->company_id);
    }

    // ── Çok-şirketli personel ───────────────────────────────────────────────

    public function test_staff_assigned_to_multiple_companies_sees_all_of_them(): void
    {
        $staff = $this->userFor($this->companyA, User::ROLE_SENIOR);

        DB::table('company_user')->insert([
            'user_id' => $staff->id,
            'company_id' => $this->companyB->id,
            'role_in_company' => 'operator',
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $staff->forgetVisibleCompanyIds();

        $visible = $staff->visibleCompanyIds();

        $this->assertContains((int) $this->companyA->id, $visible);
        $this->assertContains((int) $this->companyB->id, $visible, 'Pivottaki şirket görünür kümede yok.');
    }

    public function test_regular_company_user_sees_only_its_own_company(): void
    {
        $user = $this->userFor($this->companyA);

        $this->assertSame([(int) $this->companyA->id], $user->visibleCompanyIds());
    }

    private function makeGuestFor(\App\Models\Company $company, string $email): GuestApplication
    {
        return GuestApplication::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'tracking_token' => 'TOK-' . strtoupper(substr(md5($email), 0, 8)),
            'first_name' => 'Test',
            'last_name' => 'Guest',
            'email' => $email,
            'application_type' => 'bachelor',
            'kvkk_consent' => true,
        ]);
    }
}
