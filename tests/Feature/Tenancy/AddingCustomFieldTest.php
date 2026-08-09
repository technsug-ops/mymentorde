<?php

namespace Tests\Feature\Tenancy;

use App\Models\GuestRegistrationField;
use App\Models\User;
use App\Services\GuestRegistrationFieldSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Panelden YENİ alan eklemek formu bozuyor mu?
 *
 * ── ŞÜPHE ───────────────────────────────────────────────────────────────
 * Form satırları miras zinciriyle okunuyor: firmanın KENDİ satırları →
 * üst firmalar → fabrika (`company_id = 0`). `rowsFor()` ilk BOŞ OLMAYAN
 * kümede duruyor.
 *
 * Bugün tüm alanlar fabrika satırı; hiçbir firmanın kendi satırı yok.
 * Panelden tek bir alan eklenirse o firma "kendi satırlarını edinmiş"
 * oluyor — ve kümesi tek elemanlı. Zincir orada durursa form 114 alandan
 * 1 alana düşer.
 */
class AddingCustomFieldTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function factoryField(string $key): void
    {
        GuestRegistrationField::query()->create([
            'company_id'    => 0,
            'section_key'   => 'personal_info',
            'section_title' => 'Kişisel Bilgiler',
            'section_order' => 10,
            'field_key'     => $key,
            'label'         => strtoupper($key),
            'type'          => 'text',
            'is_required'   => false,
            'sort_order'    => 10,
            'is_active'     => true,
        ]);
    }

    /** @return list<string> */
    private function visibleKeysFor(int $companyId): array
    {
        return collect(app()->makeWith(GuestRegistrationFieldSchemaService::class, [])->groups($companyId))
            ->flatMap(fn (array $g) => $g['fields'] ?? [])
            ->pluck('key')
            ->all();
    }

    /**
     * ⚠ ASIL SORU. Merkezî tanımı miras alan bir firmaya panelden TEK alan
     * eklenirse formun geri kalanı yerinde kalıyor mu?
     */
    public function test_adding_one_custom_field_must_not_collapse_the_form(): void
    {
        // Fabrika şablonu migration'la geliyor; mutlak sayı yerine ÖNCE/SONRA
        // farkına bakıyoruz — sayı değişse de test anlamını korusun.
        $before = $this->visibleKeysFor((int) $this->companyA->id);

        $this->assertGreaterThan(50, count($before), 'Fabrika sablonu okunamadi, test bir sey olcmuyor.');
        $this->assertContains('first_name', $before);

        // Yönetici panelden tek bir yeni alan ekliyor.
        $this->actingAs($this->userFor($this->companyA, User::ROLE_MANAGER))
            ->postJson('/api/v1/config/guest-registration-fields', [
                'section_key'   => 'personal_info',
                'section_title' => 'Kişisel Bilgiler',
                'field_key'     => 'lisans_ortalama',
                'label'         => 'Lisans mezuniyet ortalaması',
                'type'          => 'text',
            ])
            ->assertCreated();

        $after = $this->visibleKeysFor((int) $this->companyA->id);

        $this->assertContains('lisans_ortalama', $after, 'Yeni alan forma girmedi.');

        $this->assertContains(
            'first_name',
            $after,
            'Tek alan eklenince MEVCUT alanlar kayboldu — miras zinciri firmanin kendi kumesinde duruyor.'
        );

        $this->assertCount(
            count($before) + 1,
            $after,
            'Form alan sayisi beklenmedik degisti: ' . count($before) . ' → ' . count($after)
        );
    }
}
