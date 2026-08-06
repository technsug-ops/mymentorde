<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\CompanyMailSetting;
use App\Models\User;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Firmanın kendi mail taşıyıcısı.
 *
 * White-label platformda gönderim kimliği firmaya ait olmalı: başka bir
 * markanın maili platformun adresinden çıkarsa white-label sözü bozulur.
 * "Kendi sunucumu / kendi Resend hesabımı kullanın" diyen firmaya verilen
 * cevap bu.
 *
 * ── BU TESTİN KORUDUĞU RİSKLER ──────────────────────────────────────────
 *  1. Test edilmemiş kimlik bilgisi devreye girerse o firmanın TÜM maili
 *     sessizce kesilir.
 *  2. Bir firmanın taşıyıcısı isteğin devamında SIZARSA sonraki firmanın
 *     maili başka markanın sunucusundan çıkar.
 *  3. Şifreler veritabanında düz metin durursa sızıntıda doğrudan kullanılır.
 */
class CompanyMailTransportTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /** companyA = ortak portal, companyB = altındaki partner. */
    private function buildHierarchy(): void
    {
        $this->companyA->update(['brand_name' => 'YourGermanUni', 'is_public_portal' => true]);
        $this->companyB->update(['brand_name' => 'Novavia', 'parent_company_id' => $this->companyA->id]);

        Company::flushHierarchyCache();
        Brand::flushCache((int) $this->companyA->id);
        Brand::flushCache((int) $this->companyB->id);
    }

    private function makeSetting(Company $company, array $attributes = []): CompanyMailSetting
    {
        $setting = CompanyMailSetting::create(array_merge([
            'company_id' => $company->id,
            'driver'     => CompanyMailSetting::DRIVER_SMTP,
            'host'       => 'mail.firma.test',
            'port'       => 587,
            'username'   => 'gonderen@firma.test',
            'password'   => 'gizli-sifre',
            'is_active'  => true,
        ], $attributes));

        CompanyMailSetting::flushActiveIds();

        return $setting;
    }

    // ── Zincir ──────────────────────────────────────────────────────────────

    public function test_company_uses_its_own_transport(): void
    {
        $this->buildHierarchy();
        $this->makeSetting($this->companyB, ['host' => 'mail.novavia.test']);

        Brand::apply($this->companyB->fresh());

        $this->assertSame('tenant_runtime', config('mail.default'));
        $this->assertSame('mail.novavia.test', config('mail.mailers.tenant_runtime.host'));
    }

    /**
     * Taşıyıcı portaldan devralınır.
     *
     * Böylece bir kez portala tanımlanıp altındaki tüm firmalarca
     * kullanılabiliyor — her partner için ayrı hesap gerekmiyor.
     */
    public function test_transport_is_inherited_from_the_portal(): void
    {
        $this->buildHierarchy();
        $this->makeSetting($this->companyA, ['host' => 'mail.yourgermanuni.test']);

        Brand::apply($this->companyB->fresh());

        $this->assertSame('mail.yourgermanuni.test', config('mail.mailers.tenant_runtime.host'));
    }

    /** Kendi taşıyıcısı portalınkini ezer. */
    public function test_own_transport_wins_over_the_portal(): void
    {
        $this->buildHierarchy();
        $this->makeSetting($this->companyA, ['host' => 'portal.test']);
        $this->makeSetting($this->companyB, ['host' => 'kendi.test']);

        Brand::apply($this->companyB->fresh());

        $this->assertSame('kendi.test', config('mail.mailers.tenant_runtime.host'));
    }

    /** Taşıyıcı yoksa platformunki kullanılır. */
    public function test_platform_transport_is_used_when_none_configured(): void
    {
        $this->buildHierarchy();
        $default = config('mail.default');

        Brand::apply($this->companyB->fresh());

        $this->assertSame($default, config('mail.default'));
    }

    // ── Risk 1: test edilmeden devreye girmemeli ────────────────────────────

    /**
     * Pasif kayıt UYGULANMAZ.
     *
     * Yanlış kimlik bilgisi o firmanın tüm mailini sessizce keser; hata
     * ancak kullanıcı "mailim gelmedi" dediğinde fark edilir. Bu yüzden
     * aktifleşme başarılı teste bağlı.
     */
    public function test_inactive_transport_is_ignored(): void
    {
        $this->buildHierarchy();
        $this->makeSetting($this->companyB, ['is_active' => false]);

        $default = config('mail.default');

        Brand::apply($this->companyB->fresh());

        $this->assertSame($default, config('mail.default'), 'Test edilmemis tasiyici devreye girdi.');
    }

    /** Eksik yapılandırma da uygulanmamalı. */
    public function test_incomplete_transport_is_ignored(): void
    {
        $this->buildHierarchy();
        $this->makeSetting($this->companyB, ['host' => '', 'port' => null]);

        $default = config('mail.default');

        Brand::apply($this->companyB->fresh());

        $this->assertSame($default, config('mail.default'));
    }

    // ── Risk 2: sızıntı ─────────────────────────────────────────────────────

    /**
     * Kuyruk işi bittiğinde taşıyıcı da iade edilmeli.
     *
     * Kuyruk web isteğinin içinde çalışıyor (KAS'ta cron yok). İade
     * edilmezse bir firmanın kendi sunucusu, aynı istekte işlenen sonraki
     * firmanın mailini de gönderirdi.
     */
    public function test_snapshot_restores_the_transport(): void
    {
        $this->buildHierarchy();
        $this->makeSetting($this->companyB, ['host' => 'novavia.test']);

        $snapshot = Brand::snapshot();
        $before   = config('mail.default');

        Brand::apply($this->companyB->fresh());
        $this->assertSame('tenant_runtime', config('mail.default'), 'Test kurulumu hatali.');

        Brand::restore($snapshot);

        $this->assertSame($before, config('mail.default'), 'Tasiyici iade edilmedi.');
        $this->assertNull(config('mail.mailers.tenant_runtime'));
    }

    /** Resend anahtarı da iade edilmeli — başka firmanın hesabı kullanılmasın. */
    public function test_snapshot_restores_the_resend_key(): void
    {
        $this->buildHierarchy();
        config(['services.resend.key' => 'platform-key']);

        $this->makeSetting($this->companyB, [
            'driver'  => CompanyMailSetting::DRIVER_RESEND,
            'api_key' => 'firma-anahtari',
        ]);

        $snapshot = Brand::snapshot();

        Brand::apply($this->companyB->fresh());
        $this->assertSame('firma-anahtari', config('services.resend.key'));

        Brand::restore($snapshot);

        $this->assertSame('platform-key', config('services.resend.key'), 'Firma anahtari sizdi.');
    }

    // ── Risk 3: sırlar ──────────────────────────────────────────────────────

    /** Şifre veritabanında DÜZ METİN durmamalı. */
    public function test_credentials_are_encrypted_at_rest(): void
    {
        $this->buildHierarchy();
        $this->makeSetting($this->companyB, ['password' => 'cok-gizli-sifre']);

        $raw = DB::table('company_mail_settings')
            ->where('company_id', $this->companyB->id)
            ->value('password');

        $this->assertNotSame('cok-gizli-sifre', $raw, 'Sifre duz metin saklandi.');
        $this->assertStringNotContainsString('cok-gizli-sifre', (string) $raw);

        // Model tarafinda dogru okunuyor mu?
        $this->assertSame(
            'cok-gizli-sifre',
            CompanyMailSetting::where('company_id', $this->companyB->id)->first()->password
        );
    }

    // ── Panel ───────────────────────────────────────────────────────────────

    /** Kaydetmek TEK BAŞINA aktifleştirmez. */
    public function test_saving_does_not_activate(): void
    {
        $owner = $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);

        $this->actingAs($owner)
            ->post("/platform/companies/{$this->companyB->id}/mail-setting", [
                'driver'   => CompanyMailSetting::DRIVER_SMTP,
                'host'     => 'mail.firma.test',
                'port'     => 587,
                'password' => 'sifre',
            ])
            ->assertRedirect();

        $setting = CompanyMailSetting::where('company_id', $this->companyB->id)->firstOrFail();

        $this->assertFalse($setting->is_active, 'Kaydetmek tek basina aktiflestirdi.');
    }

    /** Boş şifre alanı mevcut değeri SİLMEZ — panelde bir daha gösterilmiyor. */
    public function test_blank_password_keeps_the_stored_one(): void
    {
        $owner = $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
        $this->makeSetting($this->companyB, ['password' => 'eski-sifre']);

        $this->actingAs($owner)
            ->post("/platform/companies/{$this->companyB->id}/mail-setting", [
                'driver'   => CompanyMailSetting::DRIVER_SMTP,
                'host'     => 'mail.firma.test',
                'port'     => 587,
                'password' => '',
            ]);

        $this->assertSame(
            'eski-sifre',
            CompanyMailSetting::where('company_id', $this->companyB->id)->first()->password
        );
    }
}
