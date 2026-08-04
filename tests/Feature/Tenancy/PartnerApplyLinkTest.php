<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use App\Support\ApplyCompanyResolver;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * B2B partner firmanın kendi başvuru linki: /apply/{firma-slug}
 *
 * İş modeli: tüm firmaların öğrencileri yourgermanuni.com'dan kayıt olur. Kaydın
 * hangi firmaya ait olduğu firmaya özel linkten belirlenir:
 *
 *   yourgermanuni.com/apply/firma-a   →  kayıt Firma A'ya
 *   yourgermanuni.com/apply           →  kayıt varsayılan şirkete (B2C havuzu)
 *
 * Sonuç olarak:
 *   • Firma A yöneticisi yalnızca kendi öğrencisini görür
 *   • Firma B onu göremez
 *   • Platform sahibi hepsini görür (bkz. PlatformPortfolioTest)
 */
class PartnerApplyLinkTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /** Firmanın lead'ine bakacak personeli olmalı — yoksa başvuru kabul edilmez. */
    private function staffFor(Company $company): User
    {
        return $this->userFor($company, User::ROLE_MANAGER);
    }

    /** @return array<string,mixed> */
    private function applicationPayload(string $email, array $extra = []): array
    {
        return array_merge([
            'first_name' => 'Ayse',
            'last_name' => 'Yilmaz',
            'email' => $email,
            'phone' => '+49 1234567890',
            'application_type' => 'bachelor',
            'target_term' => '2026 Winter',
            'target_city' => 'Berlin',
            'language_level' => 'B1',
            'lead_source' => 'organic',
            'kvkk_consent' => '1',
            'docs_ready' => '0',
        ], $extra);
    }

    // ── Landing ─────────────────────────────────────────────────────────────

    public function test_company_apply_link_renders_with_the_company_brand(): void
    {
        $this->staffFor($this->companyB);
        $this->companyB->update(['slug' => 'firma-b', 'brand_name' => 'B Egitim Danismanlik']);

        $response = $this->get('/apply/firma-b');

        $response->assertOk();
        $response->assertSee('B Egitim Danismanlik', false);
    }

    /**
     * KVKK aydınlatma metnindeki VERİ SORUMLUSU firmanın kendisi olmalı.
     *
     * Varsayılan metinde "MentorDE" sabit yazılıydı: partner firmanın öğrencisine
     * verisini MentorDE'nin işlediği söyleniyordu. Kozmetik değil, hukuki hata.
     */
    public function test_kvkk_notice_names_the_company_not_the_platform(): void
    {
        $this->staffFor($this->companyB);
        $this->companyB->update(['slug' => 'firma-b', 'brand_name' => 'B Egitim']);

        $response = $this->get('/apply/firma-b');

        $response->assertOk();
        $response->assertSee('Kisisel verileriniz B Egitim tarafindan', false);
        $response->assertDontSee('Kisisel verileriniz MentorDE tarafindan', false);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/apply/boyle-bir-firma-yok')->assertNotFound();
    }

    /**
     * Personeli olmayan şirket başvuru kabul etmez.
     *
     * Nötr portal şirketi (yourgermanuni) tam olarak bu durumda: marka taşıyıcısı,
     * tenant değil. Oraya düşen lead'i kimse görmezdi.
     */
    public function test_company_without_staff_does_not_accept_applications(): void
    {
        $portal = Company::create([
            'name' => 'YourGermanUni Portal',
            'code' => 'portal_test',
            'slug' => 'portal-test',
            'is_active' => true,
        ]);

        $this->assertFalse(ApplyCompanyResolver::acceptsApplications($portal));
        $this->get('/apply/portal-test')->assertNotFound();
    }

    public function test_inactive_company_does_not_accept_applications(): void
    {
        $this->staffFor($this->companyB);
        $this->companyB->update(['slug' => 'firma-b', 'is_active' => false]);

        ApplyCompanyResolver::flushCache($this->companyB->fresh());

        $this->get('/apply/firma-b')->assertNotFound();
    }

    // ── Kayıt firmaya yazılır ───────────────────────────────────────────────

    public function test_application_from_company_link_belongs_to_that_company(): void
    {
        $this->staffFor($this->companyB);
        $this->companyB->update(['slug' => 'firma-b']);

        $this->get('/apply/firma-b')->assertOk();

        $this->post('/apply', $this->applicationPayload('ogrenci@firma-b.test'))
            ->assertRedirect();

        $application = GuestApplication::query()
            ->withoutGlobalScope('company')
            ->where('email', 'ogrenci@firma-b.test')
            ->firstOrFail();

        $this->assertSame(
            (int) $this->companyB->id,
            (int) $application->company_id,
            'Firma linkinden gelen basvuru yanlis sirkete yazildi.'
        );
    }

    /** Öğrencinin portal hesabı da firmaya ait olmalı — aksi halde yanlış panele düşer. */
    public function test_guest_portal_user_also_belongs_to_the_company(): void
    {
        $this->staffFor($this->companyB);
        $this->companyB->update(['slug' => 'firma-b']);

        $this->get('/apply/firma-b')->assertOk();
        $this->post('/apply', $this->applicationPayload('portal@firma-b.test'))->assertRedirect();

        $guest = User::query()
            ->withoutGlobalScope('company')
            ->where('email', 'portal@firma-b.test')
            ->firstOrFail();

        $this->assertSame((int) $this->companyB->id, (int) $guest->company_id);
    }

    /** Session kaybolsa bile formdaki gizli alan firmayı taşır. */
    public function test_hidden_form_field_attributes_the_company_without_session(): void
    {
        $this->staffFor($this->companyB);
        $this->companyB->update(['slug' => 'firma-b']);

        // Landing'e HİÇ uğramadan doğrudan POST — session'da firma yok
        $this->post('/apply', $this->applicationPayload('yedek@firma-b.test', [
            ApplyCompanyResolver::FORM_FIELD => 'firma-b',
        ]))->assertRedirect();

        $application = GuestApplication::query()
            ->withoutGlobalScope('company')
            ->where('email', 'yedek@firma-b.test')
            ->firstOrFail();

        $this->assertSame((int) $this->companyB->id, (int) $application->company_id);
    }

    // ── İzolasyon ───────────────────────────────────────────────────────────

    /**
     * Bağlamı `runFor` ile açıkça kuruyoruz.
     *
     * `actingAs()` middleware çalıştırmaz, yani SetCompanyContext devreye girmez ve
     * bağlam bir önceki isteğinkinde kalırdı — test app'i değil kendini ölçerdi.
     * `runFor`, middleware'in ürettiği bağlamın aynısını kurar.
     */
    public function test_other_company_cannot_see_the_application(): void
    {
        $this->staffFor($this->companyB);
        $this->companyB->update(['slug' => 'firma-b']);

        $this->get('/apply/firma-b')->assertOk();
        $this->post('/apply', $this->applicationPayload('gizli@firma-b.test'))->assertRedirect();

        $seenByA = TenantContext::runFor(
            (int) $this->companyA->id,
            fn () => GuestApplication::query()->where('email', 'gizli@firma-b.test')->first()
        );

        $this->assertNull($seenByA, 'Firma A, Firma B nin adayini gordu — tenant izolasyonu kirik.');
    }

    public function test_owning_company_sees_the_application(): void
    {
        $this->staffFor($this->companyB);
        $this->companyB->update(['slug' => 'firma-b']);

        $this->get('/apply/firma-b')->assertOk();
        $this->post('/apply', $this->applicationPayload('gorunur@firma-b.test'))->assertRedirect();

        $seenByB = TenantContext::runFor(
            (int) $this->companyB->id,
            fn () => GuestApplication::query()->where('email', 'gorunur@firma-b.test')->first()
        );

        $this->assertNotNull($seenByB, 'Firma kendi adayini goremiyor.');
    }

    // ── B2C davranışı korunur ───────────────────────────────────────────────

    public function test_plain_apply_still_writes_to_the_default_company(): void
    {
        $this->post('/apply', $this->applicationPayload('b2c@example.test'))
            ->assertRedirect();

        $application = GuestApplication::query()
            ->withoutGlobalScope('company')
            ->where('email', 'b2c@example.test')
            ->firstOrFail();

        $this->assertNotSame(
            (int) $this->companyB->id,
            (int) $application->company_id,
            'Firma linki kullanilmadan gelen kayit partner firmaya yazildi.'
        );
        $this->assertNotNull($application->company_id);
    }

    // ── Kurumlar arası e-posta çakışması ────────────────────────────────────

    /**
     * `users.email` GLOBAL unique. Aynı e-posta ikinci bir firmaya başvurursa
     * INSERT veritabanı seviyesinde patlardı (500). Anlaşılır hata dönmeli.
     */
    public function test_email_registered_in_another_company_is_rejected_cleanly(): void
    {
        $this->staffFor($this->companyB);
        $this->companyB->update(['slug' => 'firma-b']);

        // Önce B2C tarafında kayıt olsun
        $this->post('/apply', $this->applicationPayload('cakisan@example.test'))->assertRedirect();

        // Aynı e-posta bu kez Firma B'nin linkinden
        $this->get('/apply/firma-b')->assertOk();

        $this->post('/apply', $this->applicationPayload('cakisan@example.test'))
            ->assertSessionHasErrors('email');

        $this->assertSame(
            1,
            GuestApplication::query()->withoutGlobalScope('company')
                ->where('email', 'cakisan@example.test')->count(),
            'Cakisan e-posta ile ikinci kayit olusturuldu.'
        );
    }

    // ── Yardımcı ────────────────────────────────────────────────────────────

    public function test_link_helper_prefers_slug_over_code(): void
    {
        $this->companyB->update(['slug' => 'firma-b']);

        $this->assertStringEndsWith('/apply/firma-b', ApplyCompanyResolver::linkFor($this->companyB->fresh()));

        $this->companyA->update(['slug' => null]);

        $this->assertStringEndsWith('/apply/firma_a', ApplyCompanyResolver::linkFor($this->companyA->fresh()));
    }
}
