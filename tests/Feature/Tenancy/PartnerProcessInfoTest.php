<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentUniversityApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Partnerin "öğrencimin süreci ne durumda?" ekranı.
 *
 * Partner öğrenciyi devrediyor, operasyonu ÜST firma yürütüyor. Ekranın işi
 * partnere ne yapıldığını göstermek — süreci ilerletmek değil.
 *
 * ── BU TESTİN ASIL DERDİ ─────────────────────────────────────────────────
 * Bu projede tekrar eden bir hata sınıfı var: kayıt partnere ait olduğunda
 * firma kapsamlı sorgu HİÇBİR ŞEY bulmuyor ve ekran sessizce boş geliyor.
 * Bugüne kadar danışman ataması, öğrenciye dönüşüm, takip jetonu ve olay
 * kaydı aynı şekilde kırıldı — hepsi hatasız, hepsi sessiz.
 *
 * Burada iki yönü birden ölçüyoruz:
 *   • Partner KENDİ öğrencisinin sürecini gerçekten görüyor mu (sessiz boşluk)
 *   • BAŞKASININ öğrencisini göremiyor mu (sızıntı)
 */
class PartnerProcessInfoTest extends TestCase
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

    /** Partnere (companyB) ait bir öğrenci; danışmanı ÜST firmanın (companyA) elemanı. */
    private function partnerStudent(string $studentId = 'STU-PARTNER-1'): User
    {
        $advisor = $this->userFor($this->companyA, User::ROLE_SENIOR);

        StudentAssignment::create([
            'company_id'   => $this->companyB->id,
            'student_id'   => $studentId,
            'display_name' => 'Partner Ogrencisi',
            'senior_email' => $advisor->email,
            'is_archived'  => false,
        ]);

        return $advisor;
    }

    // ── Partner kendi öğrencisini görüyor mu ────────────────────────────────

    public function test_partner_sees_the_process_of_its_own_student(): void
    {
        $manager = $this->partnerManager();
        $this->partnerStudent();

        ProcessOutcome::create([
            'student_id'             => 'STU-PARTNER-1',
            'process_step'           => 'uni_assist',
            'outcome_type'           => 'acceptance',
            'university'             => 'TU Berlin',
            'details_tr'             => 'Uni-Assist degerlendirmesi tamamlandi.',
            'is_visible_to_student'  => true,
        ]);

        $html = $this->asStaff($manager)
            ->get('/manager/process-info/STU-PARTNER-1')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('TU Berlin', $html, 'Partner kendi ogrencisinin surecini goremiyor.');
        $this->assertStringContainsString('Uni Assist', $html);
    }

    /**
     * SESSİZ HATA KORUMASI: danışman üst firmanın elemanı.
     *
     * `User` firma kapsamlı olduğu için `$assignment->senior()` partner
     * bağlamında null döner — ekran "danışman atanmadı" derdi, oysa atanmış.
     * Controller bu yüzden kapsamsız okuyor; test o davranışı sabitliyor.
     */
    public function test_advisor_from_the_operating_company_is_visible(): void
    {
        $manager = $this->partnerManager();
        $advisor = $this->partnerStudent();

        $html = $this->asStaff($manager)
            ->get('/manager/process-info/STU-PARTNER-1')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            $advisor->email,
            $html,
            'Ust firmanin atadigi danisman partner ekraninda gorunmuyor.'
        );
    }

    // ── Görünürlük sınırları ────────────────────────────────────────────────

    /**
     * Henüz paylaşılmamış kaydın İÇERİĞİ değil, yalnızca sayısı görünür.
     *
     * Operasyon bir sonucu bilerek bekletmiş olabilir (öğrenciye önce kendisi
     * anlatmak isteyebilir). Partner işin sürdüğünü bilmeli, ama hazırlanan
     * metni okumamalı.
     */
    public function test_unshared_outcomes_leak_only_their_count(): void
    {
        $manager = $this->partnerManager();
        $this->partnerStudent();

        ProcessOutcome::create([
            'student_id'            => 'STU-PARTNER-1',
            'process_step'          => 'visa_application',
            'outcome_type'          => 'rejection',
            'details_tr'            => 'GIZLI-HENUZ-PAYLASILMADI',
            'is_visible_to_student' => false,
        ]);

        $html = $this->asStaff($manager)
            ->get('/manager/process-info/STU-PARTNER-1')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('GIZLI-HENUZ-PAYLASILMADI', $html, 'Paylasilmamis kayit sizdi.');
        $this->assertStringContainsString('paylaşıma hazırlanan', $html, 'Bekleyen kayit bilgisi hic gosterilmemis.');
    }

    /** Bayi görünürlük bayrağı partner için de geçerli. */
    public function test_university_applications_respect_the_partner_visibility_flag(): void
    {
        $manager = $this->partnerManager();
        $this->partnerStudent();

        $makeApplication = fn (string $name, bool $visible) => StudentUniversityApplication::create([
            'company_id'           => $this->companyA->id,
            'student_id'           => 'STU-PARTNER-1',
            'university_code'      => strtolower($name),
            'university_name'      => $name,
            'department_code'      => 'inf',
            'department_name'      => 'Informatik',
            'degree_type'          => 'bachelor',
            'status'               => 'submitted',
            'added_by'             => 'operasyon@example.test',
            'is_visible_to_dealer' => $visible,
        ]);

        $makeApplication('GORUNUR-UNI', true);
        $makeApplication('GIZLI-UNI', false);

        $html = $this->asStaff($manager)
            ->get('/manager/process-info/STU-PARTNER-1')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('GORUNUR-UNI', $html);
        $this->assertStringNotContainsString('GIZLI-UNI', $html, 'Partnere kapali basvuru gorunuyor.');
    }

    // ── Sızıntı ─────────────────────────────────────────────────────────────

    public function test_partner_cannot_open_another_companys_student(): void
    {
        $manager = $this->partnerManager();

        StudentAssignment::create([
            'company_id'   => $this->companyA->id,
            'student_id'   => 'STU-BASKA-FIRMA',
            'display_name' => 'Baska Firmanin Ogrencisi',
            'is_archived'  => false,
        ]);

        $this->asStaff($manager)
            ->get('/manager/process-info/STU-BASKA-FIRMA')
            ->assertNotFound();
    }

    public function test_listing_shows_only_the_partners_own_students(): void
    {
        $manager = $this->partnerManager();
        $this->partnerStudent();

        StudentAssignment::create([
            'company_id'   => $this->companyA->id,
            'student_id'   => 'STU-BASKA-FIRMA',
            'display_name' => 'Baska Firmanin Ogrencisi',
            'is_archived'  => false,
        ]);

        $html = $this->asStaff($manager)->get('/manager/process-info')->assertOk()->getContent();

        $this->assertStringContainsString('STU-PARTNER-1', $html);
        $this->assertStringNotContainsString('STU-BASKA-FIRMA', $html, 'Baska firmanin ogrencisi listede.');
    }

    // ── Salt okunurluk ──────────────────────────────────────────────────────

    /**
     * Süreci ilerletmek operasyonu yapan firmanın işi. Bu ekranda yazma yolu
     * bulunmamalı — bugün form yok, yarın biri eklerse test uyarsın.
     */
    public function test_process_info_is_read_only(): void
    {
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            if (!str_starts_with($route->uri(), 'manager/process-info')) {
                continue;
            }

            $this->assertSame(
                ['GET', 'HEAD'],
                $route->methods(),
                "Surec bilgisi salt okunur olmali ama {$route->uri()} yazma kabul ediyor."
            );
        }
    }
}
