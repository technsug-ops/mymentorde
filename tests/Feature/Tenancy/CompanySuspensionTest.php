<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use App\Support\ApplyCompanyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Şirketi askıya alma.
 *
 * Kullanım: test kayıtları, sözleşmesi biten firmalar. Pasif şirket başvuru
 * kabul etmez, MRR toplamına girmez.
 *
 * ⚠ ASIL TEHLİKE: askıya alınan firmanın kullanıcısı VARSAYILAN şirkete
 * düşüyordu — ürettiği her kayıt platformun kutusuna yazılırdı. Askıya alma
 * butonu bu tuzağı kolayca tetiklenebilir hale getirdiği için önce o kapatıldı.
 */
class CompanySuspensionTest extends TestCase
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

    // ── Yazma hedefi tuzağı ─────────────────────────────────────────────────

    /**
     * Pasif firmanın kullanıcısı platformun kutusuna YAZMAMALI.
     *
     * Eskiden middleware pasif şirketi bulamayınca varsayılana düşüyordu ve
     * yazma hedefi MentorDE oluyordu. Askıya alınmış bir firma sessizce
     * platformun verisini kirletirdi.
     */
    public function test_suspended_company_user_does_not_write_into_the_default_company(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);
        $this->companyB->update(['is_active' => false]);

        $this->actingAs($manager)->get('/login');

        $writeId = (int) \App\Support\TenantContext::writeId();

        $this->assertNotSame(
            (int) $this->primaryCompany()->id,
            $writeId,
            'Pasif firmanin kullanicisi platformun kutusuna yaziyor.'
        );
        $this->assertSame((int) $this->companyB->id, $writeId);
    }

    // ── Başvuru linki kapanır ───────────────────────────────────────────────

    public function test_suspended_company_stops_accepting_applications(): void
    {
        $this->userFor($this->companyB, User::ROLE_MANAGER);
        $this->companyB->update(['slug' => 'firma-b']);

        $this->get('/apply/firma-b')->assertOk();

        $this->companyB->update(['is_active' => false]);
        ApplyCompanyResolver::flushCache($this->companyB->fresh());

        $this->get('/apply/firma-b')->assertNotFound();
    }

    // ── Panel ───────────────────────────────────────────────────────────────

    public function test_owner_can_suspend_and_reopen_a_company(): void
    {
        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/status', ['is_active' => 0])
            ->assertRedirect();

        $this->assertFalse((bool) $this->companyB->fresh()->is_active);

        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/status', ['is_active' => 1])
            ->assertRedirect();

        $this->assertTrue((bool) $this->companyB->fresh()->is_active);
    }

    /** Ana şirket kapatılamaz — varsayılan şirket çözümlemesi ona bağlı. */
    public function test_primary_company_cannot_be_suspended(): void
    {
        $primary = $this->primaryCompany();

        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $primary->id . '/status', ['is_active' => 0])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue((bool) $primary->fresh()->is_active);
    }

    public function test_company_users_cannot_suspend_companies(): void
    {
        $firmUser = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $response = $this->actingAs($firmUser)
            ->post('/platform/companies/' . $this->companyB->id . '/status', ['is_active' => 0]);

        $this->assertContains($response->getStatusCode(), [403, 404, 302]);
        $this->assertTrue((bool) $this->companyB->fresh()->is_active);
    }

    /** Askıya alınan firmanın verisi silinmez — geri açılınca yerinde durur. */
    public function test_suspension_does_not_destroy_data(): void
    {
        $lead = \App\Support\TenantContext::runFor(
            (int) $this->companyB->id,
            fn (): GuestApplication => GuestApplication::create([
                'tracking_token' => 'tok-' . uniqid(),
                'first_name' => 'Aday',
                'last_name' => 'Test',
                'email' => 'askida@example.test',
                'application_type' => 'bachelor',
            ])
        );

        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/status', ['is_active' => 0])
            ->assertRedirect();

        $this->assertNotNull(
            GuestApplication::withoutGlobalScope('company')->find($lead->id),
            'Askiya alma veriyi sildi.'
        );
    }
}
