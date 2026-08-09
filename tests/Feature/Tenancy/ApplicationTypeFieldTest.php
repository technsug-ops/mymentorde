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
}
