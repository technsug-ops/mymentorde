<?php

namespace Tests\Feature\Tenancy;

use App\Jobs\SendNotificationJob;
use App\Models\Company;
use App\Models\NotificationDispatch;
use App\Models\StudentAssignment;
use App\Models\StudentVisaApplication;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Öğrenci süreci kayıtlarına izolasyon eklendi (2026-08-08). Bu testin işi
 * o adımın YAN ETKİSİNİ ölçmek: kapsam açıldığında kaybolabilecek okumalar.
 *
 * Bu projedeki tekrar eden hata sınıfı tam olarak burada yaşanıyor —
 * firma kapsamlı sorgu, kayıt başka firmaya aitken sessizce boş dönüyor.
 * Hata fırlatmıyor; ekran "kayıt yok" diyor ve kimse fark etmiyor.
 *
 * Üç ayrı taraf ölçülüyor:
 *   1. Kaydın SAHİBİ doğru mu (aktörün firması değil, öğrencinin firması)
 *   2. Öğrenci KENDİ kaydını görüyor mu (kaydolduğu firma farklı olsa bile)
 *   3. Kardeş firma göremiyor mu (yatay izolasyon hâlâ kapalı)
 */
class SubjectOwnedRecordsTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private const STUDENT_ID = 'STU-KONU-1';

    /** companyA = operasyonu yürüten üst firma, companyB = öğrenciyi getiren partner. */
    private function makeHierarchy(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();
    }

    private function assignStudentToPartner(): void
    {
        StudentAssignment::query()->create([
            'company_id'   => $this->companyB->id,
            'student_id'   => self::STUDENT_ID,
            'display_name' => 'Partner Ogrencisi',
            'senior_email' => 'danisman@example.test',
            'is_archived'  => false,
        ]);
    }

    // ── 1. Kayıt doğru tarafa yazılıyor mu ──────────────────────────────────

    /**
     * MentorDE personeli partnerin öğrencisi için vize kaydı açıyor.
     * Kayıt AKTÖRÜN değil ÖĞRENCİNİN firmasına ait olmalı — aksi halde
     * partner kendi öğrencisinin vizesini hiç göremez.
     */
    public function test_record_belongs_to_the_students_company_not_the_actors(): void
    {
        $this->makeHierarchy();
        $this->assignStudentToPartner();

        // Aktör üst firmanın bağlamında çalışıyor
        TenantContext::bind((int) $this->companyA->id, [(int) $this->companyA->id, (int) $this->companyB->id]);

        $visa = StudentVisaApplication::query()->create([
            'student_id' => self::STUDENT_ID,
            'visa_type'  => 'national_d',
            'status'     => 'submitted',
        ]);

        $this->assertSame(
            (int) $this->companyB->id,
            (int) $visa->fresh()->company_id,
            'Vize kaydi aktorun (ust firma) kutusuna yazildi — partner goremez.'
        );
    }

    /** Konusu çözülemeyen kayıt eski davranışta kalır: aktörün şirketi. */
    public function test_record_without_a_resolvable_subject_falls_back_to_the_actor(): void
    {
        TenantContext::bind((int) $this->companyA->id, [(int) $this->companyA->id]);

        $visa = StudentVisaApplication::query()->create([
            'student_id' => 'STU-ATAMASIZ',
            'visa_type'  => 'national_d',
            'status'     => 'preparing',
        ]);

        $this->assertSame((int) $this->companyA->id, (int) $visa->fresh()->company_id);
    }

    /** Açıkça verilen `company_id` ezilmemeli — çağıran taraf bilerek yazmıştır. */
    public function test_explicit_company_id_wins(): void
    {
        $this->assignStudentToPartner();

        TenantContext::bind((int) $this->companyA->id, [(int) $this->companyA->id]);

        $visa = StudentVisaApplication::query()->create([
            'company_id' => $this->companyA->id,
            'student_id' => self::STUDENT_ID,
            'visa_type'  => 'national_d',
            'status'     => 'preparing',
        ]);

        $this->assertSame((int) $this->companyA->id, (int) $visa->fresh()->company_id);
    }

    // ── 2. Öğrenci kendi kaydını görüyor mu ─────────────────────────────────

    /**
     * SESSİZ HATA KORUMASI. Öğrenci ortak giriş kapısından (companyA) kaydoldu
     * ama süreci partner firma (companyB) getirdi. Kapsam yalnızca
     * `users.company_id`'ye baksaydı öğrenci KENDİ vizesini göremezdi.
     */
    public function test_student_sees_their_own_record_even_when_registered_under_another_company(): void
    {
        $this->makeHierarchy();
        $this->assignStudentToPartner();

        $visa = TenantContext::runFor(
            (int) $this->companyB->id,
            fn () => StudentVisaApplication::query()->create([
                'student_id' => self::STUDENT_ID,
                'visa_type'  => 'national_d',
                'status'     => 'submitted',
            ])
        );

        // Kullanıcı kaydı ÜST firmada, süreç kaydı PARTNER firmada.
        $student = User::create([
            'name'       => 'Ogrenci',
            'email'      => 'ogrenci-' . uniqid() . '@example.test',
            'password'   => Hash::make('secret-password'),
            'role'       => User::ROLE_STUDENT,
            'company_id' => $this->companyA->id,
            'student_id' => self::STUDENT_ID,
        ]);

        $seen = $this->asCompanyContextOf($student, fn () => StudentVisaApplication::query()
            ->where('student_id', self::STUDENT_ID)
            ->first());

        $this->assertNotNull($seen, 'Ogrenci kendi vize kaydini goremiyor — sessiz bosluk.');
        $this->assertSame((int) $visa->id, (int) $seen->id);
    }

    // ── 3. Yatay izolasyon hâlâ kapalı mı ───────────────────────────────────

    public function test_a_sibling_company_cannot_see_the_record(): void
    {
        $this->assignStudentToPartner();

        $sibling = Company::create(['name' => 'Kardes', 'code' => 'kardes', 'is_active' => true]);

        TenantContext::runFor((int) $this->companyB->id, fn () => StudentVisaApplication::query()->create([
            'student_id' => self::STUDENT_ID,
            'visa_type'  => 'national_d',
            'status'     => 'submitted',
        ]));

        $seen = TenantContext::runFor(
            (int) $sibling->id,
            fn () => StudentVisaApplication::query()->where('student_id', self::STUDENT_ID)->first()
        );

        $this->assertNull($seen, 'Kardes firma baska firmanin ogrenci kaydini goruyor — sizinti.');
    }

    // ── 4. Kuyruk işi ───────────────────────────────────────────────────────

    /**
     * EN SİNSİ HÂL. Kuyruk işi DISPATCH anındaki şirketin bağlamında çalışıyor
     * (AppServiceProvider::bootTenantAwareQueue). Bildirim kaydı ise KONUSUNUN
     * şirketine yazılıyor. MentorDE personeli partnerin öğrencisine bildirim
     * tetiklediğinde ikisi ayrışıyor.
     *
     * Kapsamlı okunsaydı `find()` null döner, iş sessizce hiçbir şey yapmadan
     * biterdi: kayıt sonsuza dek "queued", mail hiç gitmez, hata da yok.
     */
    public function test_queued_job_still_finds_a_dispatch_owned_by_another_company(): void
    {
        $this->makeHierarchy();
        $this->assignStudentToPartner();

        $dispatch = TenantContext::runFor(
            (int) $this->companyB->id,
            fn () => NotificationDispatch::query()->create([
                'student_id' => self::STUDENT_ID,
                'channel'    => 'in_app',
                'category'   => 'process_update',
                'subject'    => 'Surec guncellemesi',
                'body'       => 'Vize randevusu alindi.',
                'status'     => 'queued',
            ])
        );

        $this->assertSame((int) $this->companyB->id, (int) $dispatch->company_id);

        // Job üst firmanın bağlamında çalışıyor — kuyruk bağlamı budur.
        TenantContext::bind((int) $this->companyA->id, [(int) $this->companyA->id]);

        (new SendNotificationJob((int) $dispatch->id))->handle();

        $this->assertSame(
            'sent',
            (string) NotificationDispatch::withoutGlobalScope('company')->find($dispatch->id)->status,
            'Bildirim "queued" kaldi — is kaydi bulamadi, mail sessizce hic gitmedi.'
        );
    }

    /** Kullanıcının gerçek bağlamını kurup kapatır (middleware'in yaptığı iş). */
    private function asCompanyContextOf(User $user, \Closure $callback): mixed
    {
        $previous = TenantContext::snapshot();

        try {
            TenantContext::bind((int) $user->company_id, $user->visibleCompanyIds());

            return $callback();
        } finally {
            TenantContext::restore($previous);
        }
    }
}
