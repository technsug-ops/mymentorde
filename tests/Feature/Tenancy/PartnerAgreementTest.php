<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\PartnerAgreement;
use App\Models\PartnerStudentAgreement;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Portal ↔ partner anlaşmaları ve partnerin manuel öğrenci dönüşümü.
 *
 * ── İŞİN KURALI ─────────────────────────────────────────────────────────
 * Partnerin ÖĞRENCİSİYLE yaptığı sözleşme bu sistemin konusu değil —
 * öğrenciyi portal white-label takip ediyor. Netleşmesi gereken, partnerin
 * PORTALA ödeyeceği bedel. Dönüşümün kapısı o.
 *
 * ⚠ Anlaşma kayıtları İKİ SAHİPLİ (SharedBetweenTwoCompanies): global kapsam
 * yok, sınır her sorguda elle kuruluyor. Bu testin yarısı o sınırı ölçüyor —
 * kaldırılırsa bir partner diğerinin anlaşmasını görür.
 */
class PartnerAgreementTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /** companyA = portalı işleten firma, companyB = partner. */
    private function makeHierarchy(): void
    {
        $this->companyB->update([
            'parent_company_id' => $this->companyA->id,
            'panel_mode'        => Company::PANEL_PARTNER,
        ]);

        Company::flushHierarchyCache();
        Company::flushPanelModeCache();
    }

    private function partnerManager(): User
    {
        return $this->userFor($this->companyB, User::ROLE_MANAGER);
    }

    private function operationManager(): User
    {
        return $this->userFor($this->companyA, User::ROLE_MANAGER);
    }

    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    private function signedFramework(?float $standardFee = 800.0): PartnerAgreement
    {
        return PartnerAgreement::query()->create([
            'company_id'               => $this->companyA->id,
            'partner_company_id'       => $this->companyB->id,
            'title'                    => 'Cerceve Anlasma',
            'standard_student_fee_eur' => $standardFee,
            'status'                   => PartnerAgreement::STATUS_SIGNED,
            'signed_at'                => now(),
        ]);
    }

    /**
     * Öğrenci kimliği üretimi tür kaydına bağlı; tanımsızsa dönüşüm 422 verir
     * (bkz. generateStudentIdentityFromType). Canlıda tohumlanmış durumda.
     */
    private function seedStudentType(): void
    {
        \App\Models\StudentType::query()->firstOrCreate(['code' => 'bachelor'], [
            'name_tr'    => 'Bachelor',
            'name_de'    => 'Bachelor',
            'name_en'    => 'Bachelor',
            'id_prefix'  => 'BCS',
            'is_active'  => true,
            'sort_order' => 10,
            'created_by' => 'test',
        ]);
    }

    private function partnerLead(): GuestApplication
    {
        return TenantContext::runFor((int) $this->companyB->id, fn (): GuestApplication => GuestApplication::create([
            'tracking_token'   => 'tok-' . uniqid(),
            'first_name'       => 'Partner',
            'last_name'        => 'Adayi',
            'email'            => 'aday-' . uniqid() . '@example.test',
            'application_type' => 'bachelor',
        ]));
    }

    // ── Çerçeve anlaşma ─────────────────────────────────────────────────────

    public function test_partner_signs_the_framework_agreement(): void
    {
        $this->makeHierarchy();

        $agreement = PartnerAgreement::query()->create([
            'company_id'         => $this->companyA->id,
            'partner_company_id' => $this->companyB->id,
            'title'              => 'Cerceve',
            'status'             => PartnerAgreement::STATUS_SENT,
            'sent_at'            => now(),
        ]);

        $this->asStaff($this->partnerManager())
            ->post('/manager/partner-agreements/' . $agreement->id . '/sign')
            ->assertRedirect();

        $this->assertSame(PartnerAgreement::STATUS_SIGNED, (string) $agreement->fresh()->status);
    }

    /** İmza partnerin işi — operasyon onun adına imzalayamaz. */
    public function test_operation_cannot_sign_on_behalf_of_the_partner(): void
    {
        $this->makeHierarchy();

        $agreement = PartnerAgreement::query()->create([
            'company_id'         => $this->companyA->id,
            'partner_company_id' => $this->companyB->id,
            'title'              => 'Cerceve',
            'status'             => PartnerAgreement::STATUS_SENT,
            'sent_at'            => now(),
        ]);

        $this->asStaff($this->operationManager())
            ->post('/manager/partner-agreements/' . $agreement->id . '/sign')
            ->assertForbidden();

        $this->assertSame(PartnerAgreement::STATUS_SENT, (string) $agreement->fresh()->status);
    }

    /** İki sahipli kayıt: kardeş firma başkasının anlaşmasını görmemeli. */
    public function test_a_sibling_partner_cannot_see_the_agreement(): void
    {
        $this->makeHierarchy();
        $this->signedFramework();

        $sibling = Company::create([
            'name'              => 'Kardes',
            'code'              => 'kardes',
            'is_active'         => true,
            'parent_company_id' => $this->companyA->id,
        ]);
        Company::flushHierarchyCache();

        $visible = PartnerAgreement::query()->visibleTo((int) $sibling->id)->count();

        $this->assertSame(0, $visible, 'Kardes firma baska partnerin anlasmasini goruyor — sizinti.');
    }

    // ── Öğrenci bazlı anlaşma ───────────────────────────────────────────────

    /** Çerçevede standart bedel varsa partner tek adımda kapatır. */
    public function test_partner_settles_at_the_framework_standard_fee(): void
    {
        $this->makeHierarchy();
        $this->signedFramework(800.0);
        $lead = $this->partnerLead();

        $this->asStaff($this->partnerManager())
            ->post('/manager/guests/' . $lead->id . '/partner-agreement/settle')
            ->assertRedirect();

        $agreement = PartnerStudentAgreement::query()->where('guest_application_id', $lead->id)->first();

        $this->assertNotNull($agreement, 'Anlasma olusmadi.');
        $this->assertSame(PartnerStudentAgreement::STATUS_ACCEPTED, (string) $agreement->status);
        $this->assertSame(800.0, (float) $agreement->fee_eur);
    }

    /**
     * Çerçevede standart bedel yoksa tek adım kapanmamalı — partnerin kendi
     * rakamını yazmasının kapısı açılmasın diye.
     */
    public function test_settling_needs_a_standard_fee_in_the_framework(): void
    {
        $this->makeHierarchy();
        $this->signedFramework(null);
        $lead = $this->partnerLead();

        $this->asStaff($this->partnerManager())
            ->post('/manager/guests/' . $lead->id . '/partner-agreement/settle')
            ->assertSessionHasErrors('agreement');

        $this->assertSame(0, PartnerStudentAgreement::query()->where('guest_application_id', $lead->id)->count());
    }

    public function test_partner_cannot_settle_without_a_signed_framework(): void
    {
        $this->makeHierarchy();
        $lead = $this->partnerLead();

        $this->asStaff($this->partnerManager())
            ->post('/manager/guests/' . $lead->id . '/partner-agreement/settle')
            ->assertSessionHasErrors('agreement');
    }

    /** Partner başka firmanın adayı için anlaşma açamaz. */
    public function test_partner_cannot_settle_another_companys_lead(): void
    {
        $this->makeHierarchy();
        $this->signedFramework();

        $foreignLead = TenantContext::runFor((int) $this->companyA->id, fn () => GuestApplication::create([
            'tracking_token'   => 'tok-' . uniqid(),
            'first_name'       => 'Baska',
            'last_name'        => 'Firma',
            'email'            => 'baska-' . uniqid() . '@example.test',
            'application_type' => 'bachelor',
        ]));

        $this->asStaff($this->partnerManager())
            ->post('/manager/guests/' . $foreignLead->id . '/partner-agreement/settle')
            ->assertForbidden();
    }

    /** Farklı bedel: operasyon teklif eder, partner kabul eder. */
    public function test_operation_proposes_and_partner_accepts_a_different_fee(): void
    {
        $this->makeHierarchy();
        $this->signedFramework(800.0);
        $lead = $this->partnerLead();

        $this->asStaff($this->operationManager())
            ->post('/manager/guests/' . $lead->id . '/partner-agreement/propose', ['fee_eur' => 650])
            ->assertRedirect();

        $agreement = PartnerStudentAgreement::query()->where('guest_application_id', $lead->id)->firstOrFail();
        $this->assertSame(PartnerStudentAgreement::STATUS_PROPOSED, (string) $agreement->status);

        $this->asStaff($this->partnerManager())
            ->post('/manager/partner-agreement/' . $agreement->id . '/accept')
            ->assertRedirect();

        $this->assertSame(PartnerStudentAgreement::STATUS_ACCEPTED, (string) $agreement->fresh()->status);
        $this->assertSame(650.0, (float) $agreement->fresh()->fee_eur);
    }

    // ── Dönüşüm ─────────────────────────────────────────────────────────────

    /**
     * ASIL MESELE: partner adayı öğrenciye çevirebilmeli ve bunun tek şartı
     * partner anlaşması olmalı — form/belge/paket/öğrenci sözleşmesi değil.
     * O dördü operasyonu kendi yürüten firmanın kontrol listesi.
     */
    public function test_partner_converts_the_lead_once_the_agreement_is_settled(): void
    {
        $this->makeHierarchy();
        $this->seedStudentType();
        $this->signedFramework(800.0);
        $lead = $this->partnerLead();

        $this->asStaff($this->partnerManager())
            ->post('/manager/guests/' . $lead->id . '/partner-agreement/settle')
            ->assertRedirect();

        $this->asStaff($this->partnerManager())
            ->post('/manager/guests/' . $lead->id . '/partner-convert')
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $fresh = $lead->fresh();

        $this->assertTrue((bool) $fresh->converted_to_student, 'Aday ogrenciye donusmedi.');
        $this->assertNotEmpty($fresh->converted_student_id);
    }

    /** Anlaşma yapılmadan dönüşüm açılmamalı — para tarafı netleşmeden olmaz. */
    public function test_conversion_is_blocked_without_a_settled_agreement(): void
    {
        $this->makeHierarchy();
        $lead = $this->partnerLead();

        $this->asStaff($this->partnerManager())
            ->post('/manager/guests/' . $lead->id . '/partner-convert')
            ->assertSessionHasErrors('convert');

        $this->assertFalse((bool) $lead->fresh()->converted_to_student);
    }

    /**
     * Öğrenci sözleşmesi İSTEĞE BAĞLI — kaydedilmemesi dönüşümü engellememeli.
     * (Yukarıdaki dönüşüm testi zaten sözleşmesiz geçiyor; burada tutarsız
     * bir kaydın da yolu tıkamadığını sabitliyoruz.)
     */
    public function test_recording_the_student_contract_is_optional_and_amount_free(): void
    {
        $this->makeHierarchy();
        $lead = $this->partnerLead();

        $this->asStaff($this->partnerManager())
            ->post('/manager/guests/' . $lead->id . '/partner-contract', [
                'contract_signed_on' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $fresh = $lead->fresh();

        $this->assertSame('approved', (string) $fresh->contract_status);
        // Tutar girilmediyse 0 yazılmamalı — finansa "bedava" diye girerdi.
        $this->assertNull($fresh->contract_amount_locked_at);
    }
}
