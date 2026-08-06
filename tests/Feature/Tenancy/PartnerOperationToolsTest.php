<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Operasyonun araçları partnere kapalı — ama partnerin işi açık kalmalı.
 *
 * Bu testin iki yüzü var ve ikisi de aynı derecede önemli:
 *
 *   KAPALI  Uni-Assist / vize rehberi, danışman ataması, risk & ödeme
 *           değerlendirmesi. Bunları operasyonu yürüten firma yapar;
 *           partner MentorDE'nin danışmanına dışarıdan görev veremez.
 *
 *   AÇIK    Partnerin kendi işi. `manager/guests` açılırken alt yolların
 *           TAMAMI açılmıştı; sonra DENIED listesi geldi. Fazla kapatmak,
 *           az kapatmak kadar bozar — nitekim şifre sıfırlama tam olarak
 *           böyle kırıldı: uç nokta allowlist'te olmadığı için 404 döndü
 *           ve arayüz bunu "Mail gönderilemedi." diye gösterdi.
 */
class PartnerOperationToolsTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    private function partnerManager(): User
    {
        $this->companyB->update(['panel_mode' => Company::PANEL_PARTNER]);
        Company::flushPanelModeCache();

        return $this->userFor($this->companyB, User::ROLE_MANAGER);
    }

    private function partnerGuest(): GuestApplication
    {
        return GuestApplication::create([
            'company_id'    => $this->companyB->id,
            'first_name'    => 'Aday',
            'last_name'     => 'Ogrenci',
            'email'         => 'aday-' . uniqid() . '@example.test',
            'tracking_token'   => strtoupper(uniqid()),
            'application_type' => 'bachelor',
        ]);
    }

    // ── Kapalı olması gerekenler ────────────────────────────────────────────

    public function test_operation_guides_are_closed_for_partners(): void
    {
        $manager = $this->partnerManager();
        $guest   = $this->partnerGuest();

        StudentAssignment::create([
            'company_id'  => $this->companyB->id,
            'student_id'  => 'STU-OPS-1',
            'is_archived' => false,
        ]);

        $blocked = [
            "/manager/guests/{$guest->id}/uni-assist-rehber",
            "/manager/guests/{$guest->id}/vize-rehber",
            '/manager/students/STU-OPS-1/uni-assist-rehber',
            '/manager/students/STU-OPS-1/vize-rehber',
        ];

        foreach ($blocked as $path) {
            $this->assertSame(
                404,
                $this->asStaff($manager)->get($path)->getStatusCode(),
                "Operasyona ait {$path} partnere acik kaldi."
            );
        }
    }

    /** Danışman üst firmanın elemanı — partner atayamaz, kaldıramaz. */
    public function test_partner_cannot_assign_an_advisor(): void
    {
        $manager = $this->partnerManager();
        $guest   = $this->partnerGuest();

        $this->asStaff($manager)
            ->patch("/manager/guests/{$guest->id}/assign", ['assigned_senior_email' => ''])
            ->assertNotFound();
    }

    /** Risk ve ödeme değerlendirmesi de operasyonun. */
    public function test_partner_cannot_edit_the_operational_assessment(): void
    {
        $manager = $this->partnerManager();

        StudentAssignment::create([
            'company_id'  => $this->companyB->id,
            'student_id'  => 'STU-OPS-2',
            'is_archived' => false,
        ]);

        $this->asStaff($manager)
            ->patch('/manager/students/STU-OPS-2/update', ['risk_level' => 'high'])
            ->assertNotFound();
    }

    /** Buton da görünmemeli — tıklanamayacak düğme göstermenin anlamı yok. */
    public function test_guide_buttons_are_hidden_from_the_partner(): void
    {
        $manager = $this->partnerManager();
        $guest   = $this->partnerGuest();

        $html = $this->asStaff($manager)->get("/manager/guests/{$guest->id}")->getContent();

        $this->assertStringNotContainsString('uni-assist-rehber', $html, 'Uni-Assist butonu partnere gorunuyor.');
        $this->assertStringNotContainsString('vize-rehber', $html, 'Vize butonu partnere gorunuyor.');
        $this->assertStringNotContainsString('/assign', $html, 'Danisman atama formu partnere gorunuyor.');
    }

    // ── Açık kalması gerekenler ─────────────────────────────────────────────

    /**
     * REGRESYON TESTİ.
     *
     * Şifre sıfırlama ucu allowlist'te yoktu; middleware 404 döndü, arayüz
     * de gövdesi boş gelen hatayı "Mail gönderilemedi." diye gösterdi. Hata
     * mail sunucusunda değil, bizim kısıtımızdaydı.
     *
     * Adayın hesabını partner açıyor; şifresini de sıfırlayabilmeli.
     */
    public function test_partner_can_still_reset_a_password(): void
    {
        $manager = $this->partnerManager();

        $status = $this->asStaff($manager)
            ->post('/manager/quick-admin/reset-password', [
                'email'        => 'aday@example.test',
                'default_role' => 'guest',
            ])
            ->getStatusCode();

        $this->assertNotSame(404, $status, 'Sifre sifirlama partnere kapali — mail hatasi degil, kisit hatasi.');
    }

    /** Aday ve öğrenci detayları partnerin kendi işi; kapanmamalı. */
    public function test_partner_can_open_its_own_records(): void
    {
        $manager = $this->partnerManager();
        $guest   = $this->partnerGuest();

        $this->asStaff($manager)->get("/manager/guests/{$guest->id}")->assertOk();
        $this->asStaff($manager)->get('/manager/guests')->assertOk();
    }

    // ── Tam panel etkilenmemeli ─────────────────────────────────────────────

    public function test_full_mode_company_keeps_the_operation_tools(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $guest = GuestApplication::create([
            'company_id'     => $this->companyB->id,
            'first_name'     => 'Aday',
            'last_name'      => 'Ogrenci',
            'email'          => 'tam-' . uniqid() . '@example.test',
            'tracking_token'   => strtoupper(uniqid()),
            'application_type' => 'bachelor',
        ]);

        $html = $this->asStaff($manager)->get("/manager/guests/{$guest->id}")->getContent();

        $this->assertStringContainsString('uni-assist-rehber', $html, 'Tam panelde rehber butonu kayboldu.');
        $this->assertStringContainsString('/assign', $html, 'Tam panelde danisman atama formu kayboldu.');
    }
}
