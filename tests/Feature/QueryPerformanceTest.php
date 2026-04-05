<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * N+1 sorgu tespiti — yüksek trafikli sayfalar.
 *
 * Neden bu testler var:
 *   - N+1 sorguları küçük test datasetinde görünmez, production'da (500+ kayıt) patlar.
 *   - Bu testler 10 kayıtla sınırı aşan sorgu sayısını yakalar.
 *
 * Eşik değerleri (MAX_QUERIES sabitleri):
 *   - Makul üst sınır = base queries + (ilişki başına 1-2 sorgu)
 *   - N+1 olursa = base + (kayıt sayısı × ilişki sayısı) → çok daha büyük
 *   - Başarısızlık mesajı hangi sayfanın sorunlu olduğunu gösterir.
 */
class QueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const RECORD_COUNT = 10;

    // Senior sayfaları için maksimum sorgu eşikleri
    private const MAX_SENIOR_DASHBOARD  = 50;
    private const MAX_GUEST_PIPELINE    = 20;
    private const MAX_STUDENT_LIST      = 20;

    // ── Yardımcılar ──────────────────────────────────────────────────────

    private function makeSenior(): User
    {
        return User::query()->create([
            'name'        => 'Test Senior',
            'email'       => 'senior_perf@test.local',
            'password'    => Hash::make('Secret123!'),
            'role'        => User::ROLE_SENIOR,
            'senior_code' => 'SNR-PERF-01',
            'is_active'   => true,
        ]);
    }

    /** N kayıt oluştur, hepsini bu senior'a ata */
    private function seedGuestApplications(User $senior, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            GuestApplication::query()->create([
                'tracking_token'  => "TOK-PERF-{$i}",
                'first_name'      => "Perf{$i}",
                'last_name'       => 'Test',
                'email'           => "perf{$i}@test.local",
                'application_type'=> 'bachelor',
                'kvkk_consent'    => true,
                'docs_ready'      => (bool) ($i % 2),
                'pipeline_stage'  => 'new',
                'assigned_senior_code' => $senior->senior_code,
            ]);
        }
    }

    /** N öğrenci + StudentAssignment oluştur */
    private function seedStudents(User $senior, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $studentId = "STU-PERF-{$i}";

            $studentUser = User::query()->create([
                'name'       => "Student {$i}",
                'email'      => "student_perf{$i}@test.local",
                'password'   => Hash::make('S!'),
                'role'       => User::ROLE_STUDENT,
                'student_id' => $studentId,
                'is_active'  => true,
            ]);

            StudentAssignment::query()->create([
                'student_id'  => $studentId,
                'senior_code' => $senior->senior_code,
                'status'      => 'active',
            ]);
        }
    }

    // ── Testler ──────────────────────────────────────────────────────────

    /**
     * Senior dashboard: N kayıtla sorgu sayısı sabit kalmalı.
     * N+1 olursa sorgu sayısı kayıt sayısıyla lineer büyür.
     */
    public function test_senior_dashboard_does_not_produce_n_plus_1_queries(): void
    {
        $senior = $this->makeSenior();
        $this->seedGuestApplications($senior, self::RECORD_COUNT);
        $this->seedStudents($senior, self::RECORD_COUNT);

        DB::enableQueryLog();

        $this->actingAs($senior)
            ->get('/senior/dashboard')
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(
            self::MAX_SENIOR_DASHBOARD,
            $queryCount,
            "Senior dashboard N+1 riski: {$queryCount} sorgu tetiklendi (eşik: " . self::MAX_SENIOR_DASHBOARD . "). " .
            "eager loading eksik olabilir."
        );
    }

    /**
     * Guest pipeline: 10 guest ile sorgu sayısı eşiği aşmamalı.
     */
    public function test_senior_guest_pipeline_does_not_produce_n_plus_1_queries(): void
    {
        $senior = $this->makeSenior();
        $this->seedGuestApplications($senior, self::RECORD_COUNT);

        DB::enableQueryLog();

        $this->actingAs($senior)
            ->get('/senior/guest-pipeline')
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(
            self::MAX_GUEST_PIPELINE,
            $queryCount,
            "Guest pipeline N+1 riski: {$queryCount} sorgu (eşik: " . self::MAX_GUEST_PIPELINE . ")."
        );
    }

    /**
     * Student listesi: 10 öğrenci ile sorgu sayısı eşiği aşmamalı.
     */
    public function test_senior_student_list_does_not_produce_n_plus_1_queries(): void
    {
        $senior = $this->makeSenior();
        $this->seedStudents($senior, self::RECORD_COUNT);

        DB::enableQueryLog();

        $this->actingAs($senior)
            ->get('/senior/students')
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(
            self::MAX_STUDENT_LIST,
            $queryCount,
            "Student listesi N+1 riski: {$queryCount} sorgu (eşik: " . self::MAX_STUDENT_LIST . ")."
        );
    }

    /**
     * Guest pipeline JSON poll endpoint: her poll'da minimal sorgu olmalı.
     */
    public function test_guest_pipeline_poll_is_efficient(): void
    {
        $senior = $this->makeSenior();
        $this->seedGuestApplications($senior, self::RECORD_COUNT);

        DB::enableQueryLog();

        $this->actingAs($senior)
            ->getJson('/senior/guest-pipeline/poll')
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(
            10,
            $queryCount,
            "Pipeline poll N+1 riski: {$queryCount} sorgu (eşik: 10). " .
            "Bu endpoint her 5 saniyede bir çağrılıyor."
        );
    }
}
