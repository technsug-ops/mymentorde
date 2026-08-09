<?php

namespace Tests\Feature\Tenancy;

use App\Models\GuestApplication;
use App\Models\GuestRegistrationField;
use App\Models\User;
use App\Services\GuestRegistrationFieldSchemaService;
use App\Support\ApplicationTypes;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Alan bazında başvuru türü etiketi.
 *
 * ── NEDEN AYRI FORM DEĞİL ───────────────────────────────────────────────
 * Master/Ausbildung için ayrı form açmak kopyalama sorununu üçe katlardı:
 * merkezde bir alan değişince üç tanımın da güncellenmesi gerekir, biri
 * unutulduğunda fark edilmez. Tek tanım kalıyor, alan hangi türlerde
 * görüneceğini kendisi söylüyor.
 *
 * ── BU TESTİN ASIL DERDİ ────────────────────────────────────────────────
 * Süzme TEK noktada yapılıyor; gösterim ve kayıt aynı listeden türüyor.
 * Ayrı ayrı yapılsaydı alan EKRANDA görünüp KAYITTA reddedilirdi (ya da
 * tersi) — sessiz tutarsızlık, bu projenin en pahalı hata sınıfı.
 * Testin yarısı bu eşitliği ölçüyor.
 */
class ApplicationTypeFieldTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private const SECTION = 'akademik';

    private function field(string $key, ?array $types): GuestRegistrationField
    {
        return GuestRegistrationField::query()->create([
            'company_id'       => 0,
            'section_key'      => self::SECTION,
            'section_title'    => 'Akademik',
            'section_order'    => 10,
            'field_key'        => $key,
            'label'            => strtoupper($key),
            'type'             => 'text',
            'is_required'      => false,
            'sort_order'       => 10,
            'is_active'        => true,
            'applicable_types' => $types,
        ]);
    }

    /**
     * Firmanın KENDİ alanı.
     *
     * ⚠ Panelden düzenlenen budur. `company_id = 0` yalnızca fabrika yedeği;
     * merkezî tanım operasyonu yürüten ana firmanın satırlarında durur
     * (bkz. Platform\FormTemplateController) ve API başka firmanın satırını
     * düzenlemeye izin vermez.
     */
    private function companyField(string $key, ?array $types = null): GuestRegistrationField
    {
        $field = $this->field($key, $types);

        $field->forceFill(['company_id' => $this->companyA->id])->save();

        return $field->fresh();
    }

    /** Verilen türde bir aday olarak oturum aç. */
    private function actingAsApplicant(string $applicationType): User
    {
        $user = User::query()->create([
            'name'              => 'Aday',
            'email'             => 'aday-' . uniqid() . '@example.test',
            'password'          => Hash::make('Secret123!'),
            'role'              => User::ROLE_GUEST,
            'is_active'         => true,
            'email_verified_at' => now(),
            'company_id'        => $this->companyA->id,
        ]);

        TenantContext::runFor((int) $this->companyA->id, fn () => GuestApplication::create([
            'tracking_token'   => strtoupper(uniqid()),
            'first_name'       => 'Aday',
            'last_name'        => 'Ogrenci',
            'email'            => $user->email,
            'application_type' => $applicationType,
            'guest_user_id'    => $user->id,
        ]));

        $this->actingAs($user);

        return $user;
    }

    /** @return list<string> */
    private function visibleKeys(): array
    {
        // Servis istek başına memoize ediyor; her ölçümde taze örnek.
        return collect(app()->makeWith(GuestRegistrationFieldSchemaService::class, [])->groupsByLevel(2, 0))
            ->flatMap(fn (array $g) => $g['fields'] ?? [])
            ->pluck('key')
            ->all();
    }

    // ── Süzme ───────────────────────────────────────────────────────────────

    public function test_untagged_fields_are_visible_to_every_type(): void
    {
        $this->field('herkes', null);

        foreach (ApplicationTypes::all() as $type) {
            $this->actingAsApplicant($type);

            $this->assertContains('herkes', $this->visibleKeys(), "Etiketsiz alan {$type} icin kayboldu.");
        }
    }

    public function test_tagged_field_is_hidden_from_other_types(): void
    {
        $this->field('lise_ortalama', [ApplicationTypes::BACHELOR]);
        $this->field('lisans_ortalama', [ApplicationTypes::MASTER]);

        $this->actingAsApplicant(ApplicationTypes::MASTER);
        $keys = $this->visibleKeys();

        $this->assertContains('lisans_ortalama', $keys, 'Master alani master adayinda gorunmuyor.');
        $this->assertNotContains('lise_ortalama', $keys, 'Bachelor alani master adayina sizdi.');
    }

    public function test_a_field_can_belong_to_two_types(): void
    {
        $this->field('ikili', [ApplicationTypes::MASTER, ApplicationTypes::AUSBILDUNG]);

        $this->actingAsApplicant(ApplicationTypes::AUSBILDUNG);
        $this->assertContains('ikili', $this->visibleKeys());

        $this->actingAsApplicant(ApplicationTypes::BACHELOR);
        $this->assertNotContains('ikili', $this->visibleKeys());
    }

    /**
     * ⚠ Personel bağlamında süzme YOK: yönetim ekranları, PDF ve şablon
     * karşılaştırması tüm alanları görmeli. Süzülseydi merkezî tanımı
     * yöneten kişi alanların "kaybolduğunu" sanırdı.
     */
    public function test_staff_context_sees_every_field(): void
    {
        $this->field('sadece_master', [ApplicationTypes::MASTER]);

        $this->actingAs($this->userFor($this->companyA, User::ROLE_MANAGER));

        $this->assertContains('sadece_master', $this->visibleKeys());
    }

    // ── Gösterim = kayıt ────────────────────────────────────────────────────

    /**
     * ASIL KORUMA: ekranda olmayan alan kayıtta da kabul edilmemeli.
     * İkisi ayrı hesaplansaydı biri diğerini sessizce yalanlardı.
     */
    public function test_a_hidden_field_is_also_rejected_on_save(): void
    {
        $this->field('lise_ortalama', [ApplicationTypes::BACHELOR]);
        $this->field('herkes', null);

        $this->actingAsApplicant(ApplicationTypes::MASTER);

        $service = app()->makeWith(GuestRegistrationFieldSchemaService::class, []);

        $clean = $service->sanitizePayloadByLevel([
            'lise_ortalama' => '85',
            'herkes'        => 'deger',
        ], 2, 0);

        $this->assertArrayNotHasKey('lise_ortalama', $clean, 'Gizli alan kayitta kabul edildi.');
        $this->assertArrayHasKey('herkes', $clean);
    }

    // ── Etiket temizliği ────────────────────────────────────────────────────

    /** Tanınmayan etiket alanı gizlememeli — sessiz veri kaybı olurdu. */
    public function test_unknown_tags_are_ignored_rather_than_hiding_the_field(): void
    {
        $this->field('bozuk_etiket', ['yukseklisans', 'lisans']);

        $this->actingAsApplicant(ApplicationTypes::BACHELOR);

        $this->assertContains('bozuk_etiket', $this->visibleKeys());
    }

    /** Üç türün hepsi seçiliyse kısıt yok demektir; boş saklanır. */
    public function test_selecting_every_type_means_no_restriction(): void
    {
        $this->assertSame([], ApplicationTypes::sanitizeList(ApplicationTypes::all()));
    }

    // ── Toplu işaretleme ekranı ─────────────────────────────────────────────

    public function test_the_bulk_screen_saves_tags_in_one_go(): void
    {
        $a = $this->companyField('lise_ortalama');
        $b = $this->companyField('lisans_ortalama');

        $this->actingAs($this->userFor($this->companyA, User::ROLE_MANAGER))
            ->withSession(['2fa_passed' => true])
            ->post('/manager/form-field-types', [
                'types' => [
                    $a->id => [ApplicationTypes::BACHELOR],
                    $b->id => [ApplicationTypes::MASTER, ApplicationTypes::AUSBILDUNG],
                ],
            ])
            ->assertRedirect();

        $this->assertSame([ApplicationTypes::BACHELOR], $a->fresh()->applicable_types);
        $this->assertSame(
            [ApplicationTypes::MASTER, ApplicationTypes::AUSBILDUNG],
            $b->fresh()->applicable_types
        );
    }

    /**
     * ⚠ Gönderilmeyen alan "hiçbir tür seçilmemiş" demek — kutusu boş olan
     * satır tarayıcıda hiç gönderilmez. Ekran tüm alanları listelediği için
     * bu, etiketi KALDIRMANIN tek yolu. "Dokunma" olarak yorumlansaydı bir
     * etiket bir daha asla silinemezdi.
     */
    public function test_omitting_a_field_clears_its_tags(): void
    {
        $field = $this->companyField('lise_ortalama', [ApplicationTypes::BACHELOR]);

        $this->actingAs($this->userFor($this->companyA, User::ROLE_MANAGER))
            ->withSession(['2fa_passed' => true])
            ->post('/manager/form-field-types', ['types' => []])
            ->assertRedirect();

        $this->assertNull($field->fresh()->applicable_types);
    }

    /** Ekran başka firmanın alanını listelemediği için onu değiştiremez de. */
    public function test_the_bulk_screen_cannot_touch_another_companys_field(): void
    {
        $foreign = $this->field('yabanci_alan', null);
        $foreign->forceFill(['company_id' => $this->companyB->id])->save();

        $this->companyField('kendi_alanim');

        $this->actingAs($this->userFor($this->companyA, User::ROLE_MANAGER))
            ->withSession(['2fa_passed' => true])
            ->post('/manager/form-field-types', [
                'types' => [$foreign->id => [ApplicationTypes::MASTER]],
            ])
            ->assertRedirect();

        $this->assertNull($foreign->fresh()->applicable_types, 'Baska firmanin alani degistirildi.');
    }

    public function test_the_bulk_screen_renders(): void
    {
        $this->companyField('lise_ortalama');

        $this->actingAs($this->userFor($this->companyA, User::ROLE_MANAGER))
            ->withSession(['2fa_passed' => true])
            ->get('/manager/form-field-types')
            ->assertOk()
            ->assertSee('lise_ortalama');
    }

    // ── Panelden etiketleme ─────────────────────────────────────────────────

    /** Panel kutucukları bu uca yazıyor; uç çalışmazsa ekran da çalışmaz. */
    public function test_the_api_persists_type_tags(): void
    {
        $field = $this->companyField('lisans_ortalama');

        $this->actingAs($this->userFor($this->companyA, User::ROLE_MANAGER))
            ->putJson('/api/v1/config/guest-registration-fields/' . $field->id, [
                'applicable_types' => [ApplicationTypes::MASTER],
            ])
            ->assertOk();

        $this->assertSame([ApplicationTypes::MASTER], $field->fresh()->applicable_types);
    }

    /**
     * ⚠ Etiket YALNIZCA gönderildiğinde değişmeli. Koşulsuz yazılsaydı,
     * alanın etiketiyle ilgisi olmayan her güncelleme (etiket alanını
     * göndermeyen eski bir ekran, bir betik) etiketi sessizce silerdi.
     */
    public function test_updating_another_attribute_keeps_the_tags(): void
    {
        $field = $this->companyField('lisans_ortalama', [ApplicationTypes::MASTER]);

        $this->actingAs($this->userFor($this->companyA, User::ROLE_MANAGER))
            ->putJson('/api/v1/config/guest-registration-fields/' . $field->id, [
                'label' => 'Lisans mezuniyet ortalamaniz',
            ])
            ->assertOk();

        $this->assertSame([ApplicationTypes::MASTER], $field->fresh()->applicable_types);
    }

    /** Tanınmayan tür API'de reddedilmeli — kayıttan önce yakalansın. */
    public function test_the_api_rejects_an_unknown_type(): void
    {
        $field = $this->companyField('bir_alan');

        $this->actingAs($this->userFor($this->companyA, User::ROLE_MANAGER))
            ->putJson('/api/v1/config/guest-registration-fields/' . $field->id, [
                'applicable_types' => ['yukseklisans'],
            ])
            ->assertStatus(422);
    }
}
