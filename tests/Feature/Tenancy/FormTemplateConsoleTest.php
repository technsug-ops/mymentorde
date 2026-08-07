<?php

namespace Tests\Feature\Tenancy;

use App\Models\GuestRegistrationField;
use App\Models\User;
use App\Services\GuestRegistrationFieldSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Form şablonu ekranı — tek merkez + iki yönlü sapma denetimi.
 *
 * Sapma iki yönlü sorundur ve ikisi de sessizdir:
 *   • merkezde olup firmada olmayan alan → firma yeni alanı hiç sormaz
 *   • firmada olup merkezde olmayan alan → merkezde karşılığı olmayan veri
 *
 * Konsol erişimi olmadığı için (KAS'ta SSH yok) denetim panelde duruyor.
 */
class FormTemplateConsoleTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function owner(): User
    {
        return $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
    }

    private function seedCentral(): void
    {
        app(GuestRegistrationFieldSchemaService::class)->ensureDefaults();
    }

    private function giveCompanyOwnField(string $fieldKey = 'firmaya_ozel'): void
    {
        GuestRegistrationField::query()->create([
            'company_id'    => $this->companyB->id,
            'section_key'   => 'ozel',
            'section_title' => 'Ozel',
            'section_order' => 10,
            'field_key'     => $fieldKey,
            'label'         => 'Firmaya ozel alan',
            'type'          => 'text',
            'is_required'   => false,
            'sort_order'    => 1,
            'is_active'     => true,
        ]);
    }

    public function test_no_divergence_is_reported_when_all_use_the_centre(): void
    {
        $this->seedCentral();

        $this->actingAs($this->owner())
            ->get('/platform/form-template')
            ->assertOk()
            ->assertSee('Sapma yok', false);
    }

    /**
     * İKİ YÖNÜ DE göstermeli.
     *
     * Firmanın tek bir kendi alanı varsa: merkezdeki tüm alanlar "eksik",
     * kendi alanı ise "fazla" sayılır. Kullanıcı ne kaybedeceğini
     * sıfırlamadan ÖNCE görmeli.
     */
    public function test_both_directions_are_reported(): void
    {
        $this->seedCentral();
        $this->giveCompanyOwnField();

        $html = $this->actingAs($this->owner())
            ->get('/platform/form-template')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Merkezde var, firmada yok', $html);
        $this->assertStringContainsString('Firmada var, merkezde yok', $html);
        $this->assertStringContainsString('firmaya_ozel', $html, 'Fazla alan listelenmedi.');
    }

    /** Sıfırlama firmayı merkezî şablona döndürür. */
    public function test_reset_returns_the_company_to_the_centre(): void
    {
        $this->seedCentral();
        $this->giveCompanyOwnField();

        $this->actingAs($this->owner())
            ->post("/platform/form-template/{$this->companyB->id}/reset")
            ->assertRedirect();

        $this->assertSame(
            0,
            GuestRegistrationField::withoutGlobalScope('company')
                ->where('company_id', $this->companyB->id)
                ->count()
        );
    }

    /** Sıfırlama MERKEZİ tanıma dokunmamalı. */
    public function test_reset_does_not_touch_the_central_template(): void
    {
        $this->seedCentral();
        $this->giveCompanyOwnField();

        $before = GuestRegistrationField::withoutGlobalScope('company')->where('company_id', 0)->count();

        $this->actingAs($this->owner())->post("/platform/form-template/{$this->companyB->id}/reset");

        $this->assertSame(
            $before,
            GuestRegistrationField::withoutGlobalScope('company')->where('company_id', 0)->count(),
            'Merkezi tanim silindi.'
        );
    }

    /** Platform sahibi olmayan bu ekrana giremez. */
    public function test_non_owner_cannot_access(): void
    {
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->actingAs($manager)->withSession(['2fa_passed' => true])
            ->get('/platform/form-template')
            ->assertStatus(403);
    }
}
