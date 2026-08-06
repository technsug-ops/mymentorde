<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Giden mailin GÖNDERİCİ kimliği şirkete bağlı olmalı.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Marka katmanı yalnızca `config('brand')`'i değiştiriyordu; Laravel'in
 * gönderici bilgisi (`config('mail.from')`) .env'den geliyordu. Sayfalar
 * doğru markayı gösteriyor ama HER MAİL "MentorDE" adına çıkıyordu —
 * partner firmanın kullanıcısı hiç duymadığı bir isimden hesap
 * etkinleştirme maili alıyordu.
 *
 * ⚠ ADRES AYRI BİR MESELE. Gönderici alan adı mail sağlayıcısında
 * doğrulanmış olmalı; doğrulanmamış bir adrese geçmek o firmanın TÜM
 * mailini sessizce kırar. Bu yüzden adres yalnızca açıkça tanımlanmışsa
 * değişiyor ve test bunu iki yönden de ölçüyor.
 */
class MailSenderIdentityTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /** companyA = ortak portal, companyB = altındaki partner firma. */
    private function buildPortalHierarchy(): void
    {
        $this->companyA->update([
            'brand_name'       => 'YourGermanUni',
            'is_public_portal' => true,
        ]);

        $this->companyB->update([
            'brand_name'        => 'Novavia Yurtdışı Danışmanlık',
            'parent_company_id' => $this->companyA->id,
        ]);

        Company::flushHierarchyCache();
        Brand::flushCache((int) $this->companyA->id);
        Brand::flushCache((int) $this->companyB->id);
    }

    public function test_partner_mail_carries_portal_and_firm_name(): void
    {
        $this->buildPortalHierarchy();

        Brand::apply($this->companyB->fresh());

        $this->assertSame(
            'YourGermanUni · Novavia Yurtdışı Danışmanlık',
            config('mail.from.name'),
            'Gonderen adi portal + firma olmali.'
        );
    }

    /** Platformun kendi şirketinde sade marka adı kullanılır. */
    public function test_primary_company_keeps_its_plain_name(): void
    {
        // Şirketin kodunu değiştirmek yerine platformun kendi kodunu ona
        // çeviriyoruz: "mentorde" kodu kurulumda zaten var, çakışırdı.
        config(['app.primary_company_code' => $this->companyA->code]);
        Brand::flushCache((int) $this->companyA->id);

        Brand::apply($this->companyA->fresh());

        $this->assertStringNotContainsString(' · ', (string) config('mail.from.name'));
    }

    /** Portalı olmayan bağımsız firma kendi adıyla gönderir. */
    public function test_standalone_company_uses_its_own_name(): void
    {
        $this->companyB->update(['brand_name' => 'Bagimsiz Firma']);
        Brand::flushCache((int) $this->companyB->id);

        Brand::apply($this->companyB->fresh());

        $this->assertSame('Bagimsiz Firma', config('mail.from.name'));
    }

    // ── Adres ───────────────────────────────────────────────────────────────

    /**
     * Adres tanımlanmadıysa platformunki KORUNUR.
     *
     * Doğrulanmamış bir alan adına geçmek firmanın tüm mailini kırardı.
     */
    public function test_address_stays_on_the_platform_by_default(): void
    {
        $this->buildPortalHierarchy();

        $platformAddress = config('mail.from.address');

        Brand::apply($this->companyB->fresh());

        $this->assertSame($platformAddress, config('mail.from.address'));
    }

    /** Şirket için açıkça adres tanımlanmışsa o kullanılır. */
    public function test_explicit_company_address_is_used(): void
    {
        $this->buildPortalHierarchy();

        $this->companyB->update([
            'brand_overrides' => ['mail_from_address' => 'noreply@yourgermanuni.com'],
        ]);
        Brand::flushCache((int) $this->companyB->id);

        Brand::apply($this->companyB->fresh());

        $this->assertSame('noreply@yourgermanuni.com', config('mail.from.address'));
    }

    // ── Kuyruk ──────────────────────────────────────────────────────────────

    /**
     * Kuyruk işi bittiğinde gönderici kimliği de iade edilmeli.
     *
     * Kuyruk web isteğinin içinde çalışıyor (KAS'ta cron yok). İade
     * edilmezse aynı istekte sonraki mail yanlış firma adına çıkardı.
     */
    public function test_snapshot_restores_the_sender_identity(): void
    {
        $this->buildPortalHierarchy();

        Brand::apply($this->companyA->fresh());
        $snapshot = Brand::snapshot();
        $before   = config('mail.from.name');

        Brand::apply($this->companyB->fresh());
        $this->assertNotSame($before, config('mail.from.name'), 'Test kurulumu hatali: isimler ayni.');

        Brand::restore($snapshot);

        $this->assertSame($before, config('mail.from.name'), 'Gonderen kimligi iade edilmedi.');
    }
}
