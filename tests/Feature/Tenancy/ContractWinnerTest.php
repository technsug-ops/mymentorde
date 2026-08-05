<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Sözleşme hangi firmada imzalanırsa süreç ORADA devam eder.
 *
 * İş kuralı: kişi aday aşamasında birden fazla danışmanlık firmasıyla
 * görüşebilir (bkz. PartnerApplyLinkTest). Sözleşmeyi imzalayan firma süreci
 * devralır; diğer firmada lead olarak kalmasında sakınca yoktur ve o firmaya
 * sözleşmenin başka bir kurumda imzalandığı BİLDİRİLMEZ.
 *
 * Buradaki asıl regresyon SESSİZ bir hataydı: rol yükseltme sorgusu şirket
 * kapsamlıydı; hesabı başka firmada olan aday dönüştürülünce update hiçbir
 * satıra dokunmuyor, kişi 'guest' rolünde kalıyordu. Hata da vermiyordu.
 */
class ContractWinnerTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    /** Aday, hesabı BAŞKA firmaya ait olacak şekilde kurulur. */
    private function applicantWithAccountElsewhere(): array
    {
        $accountOwner = $this->companyA;   // hesabın ait olduğu ilk firma
        $signingFirm = $this->companyB;    // sözleşmeyi imzalayacak firma

        $person = TenantContext::runFor((int) $accountOwner->id, fn (): User => User::create([
            'name' => 'Halil Yaprakli',
            'email' => 'halil@example.test',
            'password' => bcrypt('gizli-sifre-123'),
            'role' => User::ROLE_GUEST,
            'is_active' => true,
        ]));

        // İkinci firmadaki başvurusu
        $lead = TenantContext::runFor((int) $signingFirm->id, fn (): GuestApplication => GuestApplication::create([
            'tracking_token' => 'tok-' . uniqid(),
            'guest_user_id' => $person->id,
            'first_name' => 'Halil',
            'last_name' => 'Yaprakli',
            'email' => 'halil@example.test',
            'application_type' => 'bachelor',
        ]));

        return [$person, $lead, $signingFirm, $accountOwner];
    }

    /** Dönüşümü doğrudan controller üzerinden çalıştır (hazırlık kontrollerini atlamadan). */
    private function convert(GuestApplication $lead, Company $company): void
    {
        $assignment = TenantContext::runFor((int) $company->id, fn (): StudentAssignment => StudentAssignment::create([
            'student_id' => 'STU-' . strtoupper(substr(uniqid(), -6)),
            'senior_email' => null,
            'risk_level' => 'normal',
            'payment_status' => 'ok',
            'is_archived' => false,
        ]));

        $controller = app(\App\Http\Controllers\Api\GuestApplicationAdminController::class);

        $method = new \ReflectionMethod($controller, 'promoteGuestToStudent');
        $method->setAccessible(true);
        $method->invoke(
            $controller,
            (int) $lead->guest_user_id,
            (string) $assignment->student_id,
            (int) $company->id
        );

        $lead->forceFill([
            'converted_to_student' => true,
            'converted_student_id' => $assignment->student_id,
            'lead_status' => 'contract_signed',
        ])->save();
    }

    // ── Sessiz no-op regresyonu ─────────────────────────────────────────────

    public function test_account_in_another_company_is_still_promoted(): void
    {
        [$person, $lead, $signingFirm] = $this->applicantWithAccountElsewhere();

        $this->convert($lead, $signingFirm);

        $fresh = User::query()->withoutGlobalScope('company')->find($person->id);

        $this->assertSame(
            User::ROLE_STUDENT,
            (string) $fresh->role,
            'Hesap baska firmadayken rol yukseltme SESSIZCE atlandi.'
        );
        $this->assertNotEmpty($fresh->student_id, 'student_id atanmadi.');
    }

    /** Süreç imzalayan firmada devam etmeli: hesabın aidiyeti oraya geçer. */
    public function test_student_home_moves_to_the_signing_company(): void
    {
        [$person, $lead, $signingFirm] = $this->applicantWithAccountElsewhere();

        $this->convert($lead, $signingFirm);

        $fresh = User::query()->withoutGlobalScope('company')->find($person->id);

        $this->assertSame((int) $signingFirm->id, (int) $fresh->company_id);
    }

    /** İmzalayan firma artık kendi öğrencisinin hesabını görebilmeli. */
    public function test_signing_company_can_see_its_own_student(): void
    {
        [$person, $lead, $signingFirm] = $this->applicantWithAccountElsewhere();

        $this->convert($lead, $signingFirm);

        $seen = TenantContext::runFor(
            (int) $signingFirm->id,
            fn () => User::query()->where('email', 'halil@example.test')->first()
        );

        $this->assertNotNull($seen, 'Imzalayan firma kendi ogrencisinin hesabini goremiyor.');
    }

    // ── Kaybeden firma ──────────────────────────────────────────────────────

    /** Eski firmada lead olarak kalır — kişi oradaki başvurusunu görmeye devam eder. */
    public function test_person_keeps_access_to_the_previous_company(): void
    {
        [$person, $lead, $signingFirm, $accountOwner] = $this->applicantWithAccountElsewhere();

        $this->convert($lead, $signingFirm);

        $fresh = User::query()->withoutGlobalScope('company')->find($person->id);

        $this->assertContains(
            (int) $accountOwner->id,
            $fresh->visibleCompanyIds(),
            'Kisi eski firmadaki basvurusuna erisimini kaybetti.'
        );
        $this->assertContains((int) $signingFirm->id, $fresh->visibleCompanyIds());
    }

    /**
     * Kaybeden firmaya sözleşmenin BAŞKA bir kurumda imzalandığı bildirilmez.
     * Partner firmalar birbirinden haberli değildir.
     */
    public function test_losing_company_is_not_told_about_the_other_firm(): void
    {
        [$person, $lead, $signingFirm, $accountOwner] = $this->applicantWithAccountElsewhere();

        // Kaybeden firmada da bir lead olsun
        $losingLead = TenantContext::runFor((int) $accountOwner->id, fn (): GuestApplication => GuestApplication::create([
            'tracking_token' => 'tok-' . uniqid(),
            'guest_user_id' => $person->id,
            'first_name' => 'Halil',
            'last_name' => 'Yaprakli',
            'email' => 'halil@example.test',
            'application_type' => 'bachelor',
        ]));

        $this->convert($lead, $signingFirm);

        $stillThere = TenantContext::runFor(
            (int) $accountOwner->id,
            fn () => GuestApplication::query()->find($losingLead->id)
        );

        $this->assertNotNull($stillThere, 'Kaybeden firmadaki lead kayboldu.');
        $this->assertFalse(
            (bool) $stillThere->converted_to_student,
            'Kaybeden firmanin leadi de donusmus isaretlendi.'
        );

        $leakFields = trim(implode(' ', [
            (string) $stillThere->status_message,
            (string) $stillThere->lost_reason,
            (string) $stillThere->lost_note,
        ]));

        foreach ([$signingFirm->name, 'sözleşme', 'sozlesme', 'başka kurum', 'baska kurum'] as $leak) {
            $this->assertStringNotContainsStringIgnoringCase(
                $leak,
                $leakFields,
                "Kaybeden firmaya diger kurum sizdirildi: {$leakFields}"
            );
        }
    }

    /** Kaybeden firma, kişinin öğrenci OLDUĞU firmadaki kaydını göremez. */
    public function test_losing_company_cannot_see_the_signed_application(): void
    {
        [$person, $lead, $signingFirm, $accountOwner] = $this->applicantWithAccountElsewhere();

        $this->convert($lead, $signingFirm);

        $seen = TenantContext::runFor(
            (int) $accountOwner->id,
            fn () => GuestApplication::query()->find($lead->id)
        );

        $this->assertNull($seen, 'Kaybeden firma imzalanan basvuruyu gordu.');
    }

    // ── Koruma ──────────────────────────────────────────────────────────────

    /** Zaten öğrenci/personel olan bir hesabın rolü ezilmemeli. */
    public function test_existing_non_guest_account_is_not_overwritten(): void
    {
        [$person, $lead, $signingFirm] = $this->applicantWithAccountElsewhere();

        User::query()->withoutGlobalScope('company')
            ->where('id', $person->id)
            ->update(['role' => User::ROLE_MANAGER]);

        $this->convert($lead, $signingFirm);

        $fresh = User::query()->withoutGlobalScope('company')->find($person->id);

        $this->assertSame(User::ROLE_MANAGER, (string) $fresh->role, 'Personel hesabinin rolu ezildi.');
    }

    public function test_membership_row_is_created_for_the_previous_company(): void
    {
        [$person, $lead, $signingFirm, $accountOwner] = $this->applicantWithAccountElsewhere();

        $this->convert($lead, $signingFirm);

        $this->assertTrue(
            DB::table('company_user')
                ->where('user_id', $person->id)
                ->where('company_id', $accountOwner->id)
                ->exists()
        );
    }
}
