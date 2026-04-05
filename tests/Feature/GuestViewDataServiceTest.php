<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\User;
use App\Services\GuestViewDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * GuestViewDataService — profil tamamlama yüzdesi, belge sahibi ID çözümü ve
 * ilerleme yüzdesi hesaplama mantığı için testler.
 *
 * Kapsam:
 *  - calculateProfileCompletionPercent() sıfır / kısmi / tam doluluk
 *  - resolveDocumentOwnerId() dönüştürülmüş öğrenci / ham guest formatları
 *  - build() progress yüzde hesabı son "türetilmiş" adımı dışlar (bug fix #10)
 */
class GuestViewDataServiceTest extends TestCase
{
    use RefreshDatabase;

    private GuestViewDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GuestViewDataService::class);
    }

    // ── Yardımcı ─────────────────────────────────────────────────────────────

    private function makeGuest(array $attrs = []): GuestApplication
    {
        $defaults = [
            'tracking_token'   => 'TOK-VDS-' . rand(10000, 99999),
            'email'            => 'vds_guest_' . rand() . '@test.local',
            'first_name'       => '',
            'last_name'        => '',
            'application_type' => 'bachelor',
            'kvkk_consent'     => false,
        ];
        $guest = new GuestApplication;
        $guest->forceFill(array_merge($defaults, $attrs));
        $guest->save();
        return $guest;
    }

    // ── calculateProfileCompletionPercent() ───────────────────────────────────

    public function test_profile_completion_returns_zero_for_null_guest(): void
    {
        $pct = $this->service->calculateProfileCompletionPercent(null);
        $this->assertSame(0, $pct);
    }

    public function test_profile_completion_is_low_for_mostly_empty_guest(): void
    {
        // makeGuest() email + application_type otomatik doldurur → 2/16 = %12–13
        $guest = $this->makeGuest();
        $pct   = $this->service->calculateProfileCompletionPercent($guest);

        // Tam boş değil (email+type var) ama %25'in altında kalmalı
        $this->assertLessThan(25, $pct, 'Az alan dolu ise %25\'in altında olmalı.');
        $this->assertGreaterThan(0, $pct);
    }

    public function test_profile_completion_returns_100_for_fully_filled_guest(): void
    {
        $guest = $this->makeGuest([
            'first_name'             => 'Ali',
            'last_name'              => 'Yilmaz',
            'email'                  => 'ali@test.local',
            'phone'                  => '+905551234567',
            'gender'                 => 'erkek',
            'application_country'    => 'de',
            'communication_language' => 'tr',
            'application_type'       => 'bachelor',
            'target_term'            => 'WS2025',
            'target_city'            => 'Berlin',
            'language_level'         => 'B2',
            'kvkk_consent'           => true,
            // Draft alanları
            'registration_form_draft' => [
                'address_country'   => 'tr',
                'education_level'   => 'lise',
                'passport_number'   => 'A12345678',
                'motivation_text'   => 'Almanya\'da okumak istiyorum.',
            ],
        ]);

        $pct = $this->service->calculateProfileCompletionPercent($guest);
        $this->assertSame(100, $pct, 'Tüm alanlar dolu ise %100 dönmeli.');
    }

    public function test_profile_completion_returns_partial_percent(): void
    {
        // makeGuest() email + application_type'yi de otomatik doldurur → toplam 5 alan dolu
        $guest = $this->makeGuest([
            'first_name' => 'Ali',
            'last_name'  => 'Yilmaz',
            'email'      => 'ali_partial@test.local',
            'phone'      => '+905551234567',
        ]);

        $pct = $this->service->calculateProfileCompletionPercent($guest);

        // 5/16 = %31 (first_name, last_name, email, phone, application_type dolu;
        //              kvkk_consent false = 0, diğerleri boş)
        $this->assertSame(31, $pct);
    }

    public function test_profile_completion_counts_kvkk_consent_as_boolean(): void
    {
        $withoutKvkk = $this->makeGuest([
            'first_name'  => 'Ali',
            'kvkk_consent' => false,
        ]);
        $withKvkk = $this->makeGuest([
            'first_name'   => 'Ali',
            'email'        => 'kvkk@test.local',
            'kvkk_consent' => true,
        ]);

        $pctWithout = $this->service->calculateProfileCompletionPercent($withoutKvkk);
        $pctWith    = $this->service->calculateProfileCompletionPercent($withKvkk);

        $this->assertGreaterThan($pctWithout, $pctWith, 'KVKK onayı yüzdeyi artırmalı.');
    }

    // ── resolveDocumentOwnerId() ──────────────────────────────────────────────

    public function test_resolve_document_owner_returns_gst_format_for_unconverted_guest(): void
    {
        $guest = $this->makeGuest(['id' => null]); // ID auto-assigned

        $ownerId = $this->service->resolveDocumentOwnerId($guest);

        $this->assertStringStartsWith('GST-', $ownerId, 'Dönüştürülmemiş guest için GST- formatı beklenir.');
        $this->assertSame('GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT), $ownerId);
    }

    public function test_resolve_document_owner_returns_student_id_when_converted(): void
    {
        $guest = $this->makeGuest([
            'converted_student_id' => 'BCS-26-03-001',
        ]);

        $ownerId = $this->service->resolveDocumentOwnerId($guest);

        $this->assertSame('BCS-26-03-001', $ownerId, 'Dönüştürülmüş guest için student_id dönmeli.');
    }

    public function test_resolve_document_owner_falls_back_to_gst_when_student_id_is_empty(): void
    {
        $guest = $this->makeGuest([
            'converted_student_id' => '',  // boş string → GST- formatı
        ]);

        $ownerId = $this->service->resolveDocumentOwnerId($guest);

        $this->assertStringStartsWith('GST-', $ownerId);
    }

    // ── Dashboard build() — progress yüzdesi ─────────────────────────────────

    /**
     * Bug fix #10: "Kayıt Tamamlandı" son adımı türetilmiş bir özet göstergesidir.
     * Yüzde hesabında payda olarak kullanılmamalı; yalnızca ilk 4 aksiyonable adım sayılır.
     *
     * Test: 4 adımın 2'si tamamlandıysa → %50, 5/5 üzerinden %40 değil.
     */
    public function test_dashboard_progress_percent_excludes_derived_last_step(): void
    {
        $user = User::query()->create([
            'name'      => 'Guest User',
            'email'     => 'progress_pct@test.local',
            'password'  => Hash::make('Secret123!'),
            'role'      => User::ROLE_GUEST,
            'is_active' => true,
        ]);

        // 'test_no_docs' türü için GuestRequiredDocument seed'i yoktur → requiredTotal=0
        // → build() docs_ready DB değerini (true) olduğu gibi kullanır.
        // 'bachelor' kullansaydık seeded required docs docs_ready'i override ederdi.
        (new GuestApplication)->forceFill([
            'tracking_token'                => 'TOK-PROG-001',
            'email'                         => 'progress_pct@test.local',
            'first_name'                    => 'Progress',
            'last_name'                     => 'Test',
            'application_type'              => 'test_no_docs',
            'kvkk_consent'                  => true,
            'registration_form_submitted_at' => now(),
            'docs_ready'                    => true,
            'selected_package_code'         => '',   // 3. adım tamamlanmadı
            'contract_status'               => 'not_requested', // 4. adım tamamlanmadı
        ])->save();

        $request = Request::create('/guest/dashboard');
        $request->setUserResolver(fn () => $user);

        $guest = GuestApplication::where('email', 'progress_pct@test.local')->first();
        $data  = $this->service->build($request, $guest);

        // 4 aksiyonable adım: kayıt formu ✓, belgeler ✓, paket ✗, sözleşme ✗
        $this->assertSame(50, $data['progressPercent'], '2/4 tamamlandı → %50 beklenir, 2/5 (%40) değil.');
    }

    /**
     * 4 aksiyonable adım (form+docs+paket+sözleşme) tamamlandığında %100 dönmeli.
     *
     * Not: progressPercent, $progress dizisindeki ham docs_ready değerini kullanır.
     * conversionReady ise ayrı bir checklist hesabına dayanır (uploaded docs gerektirir).
     * Bu test yalnızca progressPercent'in 5. adımı (türetilmiş) hariç tuttuğunu doğrular.
     */
    public function test_dashboard_progress_percent_is_100_when_all_steps_done(): void
    {
        $user = User::query()->create([
            'name'      => 'Guest Full',
            'email'     => 'full_progress@test.local',
            'password'  => Hash::make('Secret123!'),
            'role'      => User::ROLE_GUEST,
            'is_active' => true,
        ]);

        (new GuestApplication)->forceFill([
            'tracking_token'                => 'TOK-FULL-001',
            'email'                         => 'full_progress@test.local',
            'first_name'                    => 'Full',
            'last_name'                     => 'Progress',
            'application_type'              => 'test_no_docs',
            'kvkk_consent'                  => true,
            'registration_form_submitted_at' => now(),
            'docs_ready'                    => true,
            'selected_package_code'         => 'pkg_basic',
            'contract_status'               => 'approved',
        ])->save();

        $request = Request::create('/guest/dashboard');
        $request->setUserResolver(fn () => $user);

        $guest = GuestApplication::where('email', 'full_progress@test.local')->first();
        $data  = $this->service->build($request, $guest);

        // 4/4 aksiyonable adım → 4. adım türetilmiş olmasaydı bu %80 olurdu.
        $this->assertSame(100, $data['progressPercent'], '4/4 tamamlandı → %100 beklenir (5/5=%80 değil).');
        // Diğer bileşenler ayrıca doğrulanıyor.
        $this->assertTrue($data['formCompleted'],   'formCompleted true olmalı.');
        $this->assertTrue($data['packageSelected'], 'packageSelected true olmalı.');
        $this->assertTrue($data['contractApproved'],'contractApproved true olmalı.');
        // Not: conversionReady checklist'e bağlı (yüklü belgeler gerektirir) — burada test edilmiyor.
    }
}
