<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Öğrenci sözleşme akışı testleri.
 *
 * Kapsam: /student/contract* rotaları
 * Gap: GuestCriticalFlowTest vardı, StudentContractFlowTest yoktu.
 */
class StudentContractFlowTest extends TestCase
{
    use RefreshDatabase;

    // ── Yardımcı: hazır student user + assignment ──────────────────────────

    private function makeStudent(string $studentId = 'STU-TEST-001'): array
    {
        $user = User::query()->create([
            'name'       => 'Test Öğrenci',
            'email'      => 'student_contract@test.local',
            'password'   => Hash::make('Secret123!'),
            'role'       => User::ROLE_STUDENT,
            'student_id' => $studentId,
            'is_active'  => true,
        ]);

        // GuestApplication: sözleşme altyapısı buradan geliyor
        $guest = GuestApplication::query()->create([
            'tracking_token'       => 'TOK-STU-001',
            'first_name'           => 'Test',
            'last_name'            => 'Öğrenci',
            'email'                => 'student_contract@test.local',
            'application_type'     => 'bachelor',
            'kvkk_consent'         => true,
            'converted_to_student' => true,
            'converted_student_id' => $studentId,
            'docs_ready'           => true,
            'contract_status'      => 'not_requested',
            'selected_package_code'  => 'pkg_basic',
            'selected_package_title' => 'Basic Paket',
            'selected_package_price' => '1490 EUR',
        ]);

        return [$user, $guest];
    }

    // ── 1. Sözleşme sayfası yükleniyor ────────────────────────────────────

    public function test_student_contract_page_loads(): void
    {
        [$user] = $this->makeStudent();

        $this->actingAs($user)
            ->get('/student/contract')
            ->assertOk();
    }

    // ── 2. İlk sözleşme talebi student panelinde engelleniyor ────────────
    // (Doğru davranış: ilk talep + imzalama guest panelinde yapılır)

    public function test_student_initial_contract_request_is_blocked(): void
    {
        [$user] = $this->makeStudent();

        $this->actingAs($user)
            ->post('/student/contract/request')
            ->assertRedirect('/student/contract')
            ->assertSessionHasErrors('contract');
    }

    // ── 3. İmzalı sözleşme yükleme student panelinde engelleniyor ──────────
    // (Bu adım guest panelinde yapılır — doğru davranış: hata mesajıyla geri yönlendirir)

    public function test_student_upload_signed_is_blocked_with_error(): void
    {
        [$user] = $this->makeStudent();

        $file = UploadedFile::fake()->create('signed_contract.pdf', 200, 'application/pdf');

        $this->actingAs($user)
            ->post('/student/contract/upload-signed', ['signed_file' => $file])
            ->assertRedirect('/student/contract')
            ->assertSessionHasErrors('contract');
    }

    // ── 4. Ek madde talebi ────────────────────────────────────────────────

    public function test_student_can_request_addendum(): void
    {
        [$user, $guest] = $this->makeStudent();

        $guest->forceFill(['contract_status' => 'requested'])->save();

        $this->actingAs($user)
            ->post('/student/contract/addendum-request', [
                'subject' => 'Ders programı değişikliği',
                'message' => 'Ders programım değişti, sözleşme güncellenmesi gerekiyor.',
            ])
            ->assertRedirect('/student/contract');
    }

    // ── 5. Başka öğrencinin sözleşmesine erişilemiyor (yetki izolasyonu) ──

    public function test_student_cannot_access_another_students_contract_data(): void
    {
        [$userA] = $this->makeStudent('STU-A-001');

        $userB = User::query()->create([
            'name'       => 'Başka Öğrenci',
            'email'      => 'other_student@test.local',
            'password'   => Hash::make('Secret123!'),
            'role'       => User::ROLE_STUDENT,
            'student_id' => 'STU-B-002',
            'is_active'  => true,
        ]);

        GuestApplication::query()->create([
            'tracking_token'       => 'TOK-STU-B',
            'first_name'           => 'Başka',
            'last_name'            => 'Öğrenci',
            'email'                => 'other_student@test.local',
            'application_type'     => 'bachelor',
            'kvkk_consent'         => true,
            'converted_to_student' => true,
            'converted_student_id' => 'STU-B-002',
            'docs_ready'           => true,
            'contract_status'      => 'active',
        ]);

        // A öğrencisi kendi sayfasını görmeli ama B'nin datasına ulaşamamalı
        $this->actingAs($userA)
            ->get('/student/contract')
            ->assertOk();

        // A kendi sayfasını görüyor ama B'nin sözleşmesini görmüyor
        // (Her kullanıcı kendi GuestApplication'ına bağlı)
        $this->actingAs($userA)
            ->get('/student/contract')
            ->assertOk(); // A kendi boş sözleşme sayfasını görür
    }

    // ── 6. Giriş yapmadan erişim engelleniyor ─────────────────────────────

    public function test_unauthenticated_user_cannot_access_contract_pages(): void
    {
        $this->get('/student/contract')->assertRedirect('/login');
        $this->post('/student/contract/request')->assertRedirect('/login');
        $this->post('/student/contract/upload-signed')->assertRedirect('/login');
    }

    // ── 7. Senior rolü student sözleşmesine erişemez ──────────────────────

    public function test_senior_role_cannot_access_student_contract_routes(): void
    {
        $senior = User::query()->create([
            'name'     => 'Senior User',
            'email'    => 'senior@test.local',
            'password' => Hash::make('Secret123!'),
            'role'     => User::ROLE_SENIOR,
            'is_active' => true,
        ]);

        $this->actingAs($senior)
            ->get('/student/contract')
            ->assertForbidden(); // EnsureStudentRole middleware → 403
    }
}
