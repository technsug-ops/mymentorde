<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Yanıt adresi — öğrencinin "Yanıtla" dediğinde ulaşacağı yer.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Mailler ortak portalın adresinden çıkıyor (alan adı doğrulaması oraya
 * bağlı). Reply-To konmazsa TÜM firmaların yanıtları o tek gelen kutusunda
 * karışır ve hangisinin kime ait olduğu anlaşılmaz.
 *
 * "noreply@" yapıp kapatmak çözüm değil: öğrenci yine yanıtlar, sadece
 * cevabı hiçbir yere ulaşmaz — sessiz kayıp.
 */
class MailReplyToTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function buildHierarchy(): void
    {
        $this->companyA->update([
            'brand_name'       => 'YourGermanUni',
            'is_public_portal' => true,
            'brand_overrides'  => ['reply_to_address' => 'destek@yourgermanuni.test'],
        ]);

        $this->companyB->update([
            'brand_name'        => 'Novavia',
            'parent_company_id' => $this->companyA->id,
        ]);

        Company::flushHierarchyCache();
        Brand::flushCache((int) $this->companyA->id);
        Brand::flushCache((int) $this->companyB->id);
    }

    /** Firmanın kendi yanıt adresi kullanılır. */
    public function test_company_reply_address_is_used(): void
    {
        $this->buildHierarchy();

        $this->companyB->update(['brand_overrides' => ['reply_to_address' => 'info@novavia.test']]);
        Brand::flushCache((int) $this->companyB->id);

        Brand::apply($this->companyB->fresh());

        $this->assertSame('info@novavia.test', config('mail.reply_to.address'));
    }

    /**
     * Yanıt adresi PORTALDAN devralınır.
     *
     * Firma adres vermezse yanıtlar üst kata düşer — hiçbir mail cevapsız
     * kalmaz, yalnızca muhatabı değişir.
     */
    public function test_reply_address_is_inherited_from_the_portal(): void
    {
        $this->buildHierarchy();

        Brand::apply($this->companyB->fresh());

        $this->assertSame('destek@yourgermanuni.test', config('mail.reply_to.address'));
    }

    /** Ayrı alan yoksa firmanın destek adresi yeterli. */
    public function test_support_email_serves_as_reply_address(): void
    {
        $this->buildHierarchy();

        $this->companyB->update(['brand_overrides' => ['support_email' => 'yardim@novavia.test']]);
        Brand::flushCache((int) $this->companyB->id);

        Brand::apply($this->companyB->fresh());

        $this->assertSame('yardim@novavia.test', config('mail.reply_to.address'));
    }

    // ── Başlık gerçekten konuyor mu ─────────────────────────────────────────

    /**
     * ASIL GARANTİ: başlık giden maile gerçekten ekleniyor.
     *
     * Yapılandırmayı ayarlamak tek başına yetmez — Laravel bu değeri mailer
     * OLUŞTURULURKEN okuyor. Önbellekteki bir mailer varsa ayar ona hiç
     * ulaşmaz. Bu test uçtan uca ölçtüğü için o tuzağı da kapsar.
     */
    public function test_reply_to_header_is_attached_to_sent_mail(): void
    {
        $this->buildHierarchy();
        Brand::apply($this->companyB->fresh());

        $captured = null;
        Event::listen(MessageSending::class, function ($event) use (&$captured) {
            $captured = $event->message;
        });

        Mail::raw('deneme', fn ($msg) => $msg->to('ogrenci@example.test')->subject('deneme'));

        $this->assertNotNull($captured, 'Mail hic gonderilmedi.');

        $replyTo = $captured->getReplyTo();

        $this->assertNotEmpty($replyTo, 'Reply-To baslikta yok — ogrencinin yaniti ortak kutuya duserdi.');
        $this->assertSame('destek@yourgermanuni.test', $replyTo[0]->getAddress());
    }

    /**
     * Mail kendi yanıt adresini belirtmişse o adres KAYBOLMAZ.
     *
     * Bazı mailler (pazarlama şablonu gibi) kendi muhatabını seçiyor.
     * Laravel'in genel yanıt adresi mailer düzeyinde konuyor, mailin kendi
     * eklediği adres onun ÜSTÜNE biniyor — ikisi birden taşınıyor. Yani
     * özel muhatap her hâlükârda yanıtı alır.
     */
    public function test_explicit_reply_to_is_preserved(): void
    {
        $this->buildHierarchy();
        Brand::apply($this->companyB->fresh());

        $captured = null;
        Event::listen(MessageSending::class, function ($event) use (&$captured) {
            $captured = $event->message;
        });

        Mail::raw('deneme', function ($msg) {
            $msg->to('ogrenci@example.test')->subject('deneme')->replyTo('ozel@example.test');
        });

        $addresses = array_map(fn ($a) => $a->getAddress(), $captured->getReplyTo());

        $this->assertContains('ozel@example.test', $addresses, 'Mailin kendi yanit adresi kayboldu.');
    }

    // ── Kuyruk ──────────────────────────────────────────────────────────────

    /** Kuyruk işi bittiğinde yanıt adresi de iade edilmeli. */
    public function test_snapshot_restores_the_reply_address(): void
    {
        $this->buildHierarchy();

        $this->companyB->update(['brand_overrides' => ['reply_to_address' => 'info@novavia.test']]);
        Brand::flushCache((int) $this->companyB->id);

        Brand::apply($this->companyA->fresh());
        $snapshot = Brand::snapshot();
        $before   = config('mail.reply_to.address');

        Brand::apply($this->companyB->fresh());
        $this->assertNotSame($before, config('mail.reply_to.address'), 'Test kurulumu hatali.');

        Brand::restore($snapshot);

        $this->assertSame($before, config('mail.reply_to.address'), 'Yanit adresi iade edilmedi.');
    }
}
