<?php

namespace Tests\Feature\Tenancy;

use App\Models\SeniorResponseTemplate;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Fabrika şablonları: herkes GÖRÜR, kimse DEĞİŞTİREMEZ.
 *
 * `company_id = 0` bu projede sahipsizlik değil, bilinçli "herkesin miras
 * aldığı şablon" işareti (form tanımı ve hizmet kataloğu böyle çalışıyor).
 * İki yönlü bir yükümlülük getiriyor:
 *
 *   • Kapsam onları gizlememeli — gizlerse firma merkezî şablonu hiç
 *     göremez ve sabit kataloğa düşer.
 *   • Firma onları YAZAMAMALI — tek satır tüm firmalarda görünüyor, bir
 *     firmanın düzenlemesi diğerlerinin metnini de değiştirirdi.
 *
 * Eski `owner_user_id` kontrolü ikinci maddeye açıktı: fabrika satırında
 * owner boş olduğu için koşul hiç tetiklenmiyordu.
 */
class FactoryTemplateSharingTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function factoryTemplate(): SeniorResponseTemplate
    {
        return SeniorResponseTemplate::query()->create([
            'company_id' => 0,
            'category'   => 'general',
            'title'      => 'Fabrika sablonu',
            'body'       => 'Merhaba, sureciniz devam ediyor.',
            'is_active'  => true,
        ]);
    }

    public function test_factory_rows_are_visible_to_every_company(): void
    {
        $template = $this->factoryTemplate();

        foreach ([$this->companyA, $this->companyB] as $company) {
            $seen = TenantContext::runFor(
                (int) $company->id,
                fn () => SeniorResponseTemplate::query()->find($template->id)
            );

            $this->assertNotNull(
                $seen,
                $company->name . ' fabrika sablonunu goremiyor — merkezi sablon firmaya ulasmiyor.'
            );
        }
    }

    /** Bir firmanın kendi şablonu diğerine sızmamalı. */
    public function test_a_companys_own_template_stays_private(): void
    {
        $own = TenantContext::runFor((int) $this->companyA->id, fn () => SeniorResponseTemplate::query()->create([
            'category'  => 'general',
            'title'     => 'A firmasina ozel',
            'body'      => 'Ozel metin.',
            'is_active' => true,
        ]));

        $this->assertSame((int) $this->companyA->id, (int) $own->company_id);

        $seen = TenantContext::runFor(
            (int) $this->companyB->id,
            fn () => SeniorResponseTemplate::query()->find($own->id)
        );

        $this->assertNull($seen, 'Firmanin kendi sablonu kardes firmaya sizdi.');
    }

    public function test_a_company_cannot_edit_a_factory_template(): void
    {
        $template = $this->factoryTemplate();
        $senior = $this->userFor($this->companyA, User::ROLE_SENIOR);

        $this->actingAs($senior)
            ->withSession(['2fa_passed' => true])
            ->putJson('/senior/response-templates/' . $template->id, ['title' => 'Ele gecirildi'])
            ->assertForbidden();

        $this->assertSame(
            'Fabrika sablonu',
            (string) $template->fresh()->title,
            'Fabrika sablonu degistirildi — tum firmalarin metni etkilenirdi.'
        );
    }

    public function test_a_company_cannot_delete_a_factory_template(): void
    {
        $template = $this->factoryTemplate();
        $senior = $this->userFor($this->companyA, User::ROLE_SENIOR);

        $this->actingAs($senior)
            ->withSession(['2fa_passed' => true])
            ->deleteJson('/senior/response-templates/' . $template->id)
            ->assertForbidden();

        $this->assertNotNull($template->fresh(), 'Fabrika sablonu silindi.');
    }
}
