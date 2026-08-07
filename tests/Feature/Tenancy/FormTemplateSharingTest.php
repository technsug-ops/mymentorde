<?php

namespace Tests\Feature\Tenancy;

use App\Models\GuestRegistrationField;
use App\Services\GuestRegistrationFieldSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Form tanımı MERKEZİ kalmalı — firmaya sessizce kopyalanmamalı.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Eskiden bir firma formu ilk kez açtığında katalogun TAMAMI ona
 * kopyalanıyordu. Kopya oluştuğu an firma kalıcı olarak ayrışıyor:
 * merkezden yapılan değişiklik ona ULAŞMIYOR ve "ortak şablona düş" yedeği
 * bir daha çalışmıyor.
 *
 * Sonuç fark edilmesi zor bir sessiz sapma: form güncellenir, bazı firmalar
 * eski formda kalır, kimse anlamaz. Alt firma sayısı arttıkça hata
 * olasılığı artar — kullanıcının "unutmaya açık olmamalı" dediği tam da bu.
 */
class FormTemplateSharingTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function service(): GuestRegistrationFieldSchemaService
    {
        return app(GuestRegistrationFieldSchemaService::class);
    }

    /** ASIL GARANTİ: firma bağlamında form açmak kopya ÜRETMEZ. */
    public function test_rendering_for_a_company_creates_no_copy(): void
    {
        $this->service()->groups((int) $this->companyB->id);

        $copies = GuestRegistrationField::query()
            ->where('company_id', $this->companyB->id)
            ->count();

        $this->assertSame(0, $copies, 'Firma icin sessizce form kopyasi olusturuldu.');
    }

    /** Ortak şablon yoksa tohumlanır — ama company_id = 0 olarak. */
    public function test_seeding_goes_to_the_shared_template(): void
    {
        $this->service()->ensureDefaults((int) $this->companyB->id);

        $this->assertGreaterThan(
            0,
            GuestRegistrationField::query()->where('company_id', 0)->count(),
            'Ortak sablon tohumlanmadi.'
        );

        $this->assertSame(
            0,
            GuestRegistrationField::query()->where('company_id', $this->companyB->id)->count(),
            'Tohumlama firmaya yazdi.'
        );
    }

    /**
     * Firma kendi satırı olmadan ortak şablonu görmeli.
     *
     * Kopyalamayı durdurmanın karşılığı bu: form boş açılmamalı.
     */
    public function test_company_sees_the_shared_template(): void
    {
        $this->service()->ensureDefaults();

        $groups = $this->service()->groups((int) $this->companyB->id);

        $this->assertNotEmpty($groups, 'Firma bosluk gordu — ortak sablona dusulmedi.');
    }

    /**
     * Merkezî değişiklik firmaya ULAŞMALI.
     *
     * Kullanıcının asıl derdi buydu: "formu değiştiririm, alt firmalarda
     * değişmez, unuturum". Bu test o senaryoyu doğrudan yürütüyor.
     */
    public function test_central_change_reaches_the_company(): void
    {
        $this->service()->ensureDefaults();

        $field = GuestRegistrationField::query()->where('company_id', 0)->firstOrFail();
        $field->forceFill(['label' => 'MERKEZDEN DEGISTI'])->save();

        $labels = collect($this->service()->groups((int) $this->companyB->id))
            ->flatMap(fn ($group) => collect($group['fields'] ?? [])->pluck('label'))
            ->all();

        $this->assertContains('MERKEZDEN DEGISTI', $labels, 'Merkezi degisiklik firmaya ulasmadi.');
    }

    /** Bilerek özelleştiren firma kendi satırlarını kullanır. */
    public function test_deliberate_customisation_still_wins(): void
    {
        $this->service()->ensureDefaults();

        GuestRegistrationField::query()->create([
            'company_id'    => $this->companyB->id,
            'section_key'   => 'ozel',
            'section_title' => 'Ozel Bolum',
            'section_order' => 10,
            'field_key'     => 'ozel_alan',
            'label'         => 'FIRMAYA OZEL',
            'type'          => 'text',
            'is_required'   => false,
            'sort_order'    => 1,
            'is_active'     => true,
        ]);

        $labels = collect($this->service()->groups((int) $this->companyB->id))
            ->flatMap(fn ($group) => collect($group['fields'] ?? [])->pluck('label'))
            ->all();

        $this->assertContains('FIRMAYA OZEL', $labels);
    }
}
