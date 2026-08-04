<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\ConsentRecord;
use App\Models\GuestApplication;
use App\Models\User;
use App\Services\LeadTransferService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Adayın elle başka firmaya devri.
 *
 * Firma kendi başvuru linkini (/apply/{slug}) kullandıramadığında kayıt B2C
 * havuzuna düşer; platform sahibi buradan doğru firmaya taşır.
 *
 * EN KRİTİK TEST: yarım devir olmamalı. Bağlı satırlardan biri eski şirkette
 * kalırsa tenant filtresi arkasında görünmez olur — ne eski ne yeni firma erişir.
 */
class LeadTransferTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function owner(): User
    {
        return $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
    }

    private function leadIn(Company $company, string $email = 'aday@example.test'): GuestApplication
    {
        return TenantContext::runFor((int) $company->id, function () use ($email): GuestApplication {
            $guest = User::create([
                'name' => 'Aday Ogrenci',
                'email' => $email,
                'password' => bcrypt('secret-password'),
                'role' => User::ROLE_GUEST,
            ]);

            return GuestApplication::create([
                'tracking_token' => 'tok-' . uniqid(),
                'guest_user_id' => $guest->id,
                'first_name' => 'Aday',
                'last_name' => 'Ogrenci',
                'email' => $email,
                'application_type' => 'bachelor',
            ]);
        });
    }

    // ── Temel devir ─────────────────────────────────────────────────────────

    public function test_owner_can_transfer_a_lead_to_another_company(): void
    {
        $lead = $this->leadIn($this->companyA);

        $this->actingAs($this->owner())
            ->post('/platform/leads/' . $lead->id . '/transfer', [
                'company_id' => $this->companyB->id,
            ])
            ->assertRedirect();

        $fresh = GuestApplication::withoutGlobalScope('company')->find($lead->id);

        $this->assertSame((int) $this->companyB->id, (int) $fresh->company_id);
    }

    /** Öğrencinin portal hesabı da gitmeli — yoksa giriş yapınca eski firmaya düşer. */
    public function test_transfer_moves_the_guest_portal_user(): void
    {
        $lead = $this->leadIn($this->companyA, 'portal-devir@example.test');

        app(LeadTransferService::class)->transfer($lead, $this->companyB);

        $guest = User::withoutGlobalScope('company')->find($lead->guest_user_id);

        $this->assertSame((int) $this->companyB->id, (int) $guest->company_id);
    }

    /**
     * YARIM DEVİR OLMAMALI.
     *
     * Adaya bağlı ve company_id taşıyan hiçbir satır eski şirkette kalmamalı.
     * Sabit liste yerine şemayı tarıyoruz — servis de öyle çalışıyor, böylece
     * yeni bir tablo eklendiğinde bu test onu da kapsar.
     */
    public function test_no_related_row_is_left_behind_in_the_old_company(): void
    {
        $lead = $this->leadIn($this->companyA, 'butun-devir@example.test');

        // Bağlı kayıtlar üret
        ConsentRecord::create([
            'company_id' => $this->companyA->id,
            'user_id' => $lead->guest_user_id,
            'application_id' => $lead->id,
            'consent_type' => 'kvkk',
            'consent_given' => true,
            'accepted_at' => now(),
        ]);

        DB::table('lead_source_data')->insert([
            'company_id' => $this->companyA->id,
            'guest_id' => (string) $lead->id,
            'initial_source' => 'organic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(LeadTransferService::class)->transfer($lead, $this->companyB);

        $leftBehind = [];

        foreach (['consent_records' => 'application_id', 'lead_source_data' => 'guest_id'] as $table => $column) {
            $stale = DB::table($table)
                ->whereIn($column, [(int) $lead->id, (string) $lead->id])
                ->where('company_id', $this->companyA->id)
                ->count();

            if ($stale > 0) {
                $leftBehind[$table] = $stale;
            }
        }

        $this->assertSame(
            [],
            $leftBehind,
            'Bu tablolardaki satirlar eski sirkette kaldi: ' . json_encode($leftBehind)
        );
    }

    /** Devirden sonra hedef firma görebilmeli, eski firma görememeli. */
    public function test_visibility_follows_the_transfer(): void
    {
        $lead = $this->leadIn($this->companyA, 'gorunurluk@example.test');

        app(LeadTransferService::class)->transfer($lead, $this->companyB);

        $seenByOld = TenantContext::runFor(
            (int) $this->companyA->id,
            fn () => GuestApplication::query()->where('id', $lead->id)->first()
        );
        $seenByNew = TenantContext::runFor(
            (int) $this->companyB->id,
            fn () => GuestApplication::query()->where('id', $lead->id)->first()
        );

        $this->assertNull($seenByOld, 'Eski firma devredilen adayi hala goruyor.');
        $this->assertNotNull($seenByNew, 'Yeni firma devredilen adayi goremiyor.');
    }

    /** Eski firmanın danışmanı yeni firmada görünmez — bayat atama bırakılmamalı. */
    public function test_stale_senior_assignment_is_cleared(): void
    {
        $lead = $this->leadIn($this->companyA, 'danisman@example.test');

        GuestApplication::withoutGlobalScope('company')
            ->where('id', $lead->id)
            ->update(['assigned_senior_email' => 'senior@firma-a.test']);

        $result = app(LeadTransferService::class)->transfer($lead->fresh(), $this->companyB);

        $this->assertTrue($result['senior_cleared']);
        $this->assertNull(
            GuestApplication::withoutGlobalScope('company')->find($lead->id)->assigned_senior_email
        );
    }

    // ── Sınırlar ────────────────────────────────────────────────────────────

    public function test_converted_student_cannot_be_transferred_here(): void
    {
        $lead = $this->leadIn($this->companyA, 'donusmus@example.test');

        GuestApplication::withoutGlobalScope('company')
            ->where('id', $lead->id)
            ->update(['converted_to_student' => true]);

        $this->actingAs($this->owner())
            ->post('/platform/leads/' . $lead->id . '/transfer', ['company_id' => $this->companyB->id])
            ->assertSessionHasErrors('company_id');

        $this->assertSame(
            (int) $this->companyA->id,
            (int) GuestApplication::withoutGlobalScope('company')->find($lead->id)->company_id
        );
    }

    public function test_transfer_to_the_same_company_is_rejected(): void
    {
        $lead = $this->leadIn($this->companyA);

        $this->actingAs($this->owner())
            ->post('/platform/leads/' . $lead->id . '/transfer', ['company_id' => $this->companyA->id])
            ->assertSessionHasErrors('company_id');
    }

    public function test_transfer_to_inactive_company_is_rejected(): void
    {
        $lead = $this->leadIn($this->companyA);
        $this->companyB->update(['is_active' => false]);

        $this->actingAs($this->owner())
            ->post('/platform/leads/' . $lead->id . '/transfer', ['company_id' => $this->companyB->id])
            ->assertSessionHasErrors('company_id');
    }

    // ── Yetki ───────────────────────────────────────────────────────────────

    public function test_company_users_cannot_transfer_leads(): void
    {
        $lead = $this->leadIn($this->companyA);
        $firmUser = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $response = $this->actingAs($firmUser)
            ->post('/platform/leads/' . $lead->id . '/transfer', ['company_id' => $this->companyB->id]);

        $this->assertContains($response->getStatusCode(), [403, 404, 302]);

        $this->assertSame(
            (int) $this->companyA->id,
            (int) GuestApplication::withoutGlobalScope('company')->find($lead->id)->company_id,
            'Firma kullanicisi aday devredebildi — yetki acigi.'
        );
    }

    public function test_guests_cannot_transfer_leads(): void
    {
        $lead = $this->leadIn($this->companyA);

        $response = $this->post('/platform/leads/' . $lead->id . '/transfer', [
            'company_id' => $this->companyB->id,
        ]);

        $this->assertContains($response->getStatusCode(), [401, 403, 404, 302]);
    }
}
