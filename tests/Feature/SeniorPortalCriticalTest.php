<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Senior portal kritik akış testleri.
 *
 * SeniorPortalController 1.741 satır / ~70 metod — hiç testi yoktu.
 * Bu dosya en kritik rotaları kapsar:
 *   - Dashboard, student listesi, student detayı
 *   - Guest pipeline (görüntüleme + taşıma)
 *   - Batch review (belge onay/red)
 *   - Yetki izolasyonu (başka senior'ın datasına erişim engeli)
 */
class SeniorPortalCriticalTest extends TestCase
{
    use RefreshDatabase;

    // ── Yardımcılar ──────────────────────────────────────────────────────

    private function makeSenior(string $email = 'senior@test.local', string $code = 'SNR-001'): User
    {
        return User::query()->create([
            'name'        => 'Test Senior',
            'email'       => $email,
            'password'    => Hash::make('Secret123!'),
            'role'        => User::ROLE_SENIOR,
            'senior_code' => $code,
            'is_active'   => true,
        ]);
    }

    private function makeGuest(string $seniorCode, string $token = 'TOK-001'): GuestApplication
    {
        return GuestApplication::query()->create([
            'tracking_token'       => $token,
            'first_name'           => 'Guest',
            'last_name'            => 'Test',
            'email'                => "guest_{$token}@test.local",
            'application_type'     => 'bachelor',
            'kvkk_consent'         => true,
            'docs_ready'           => false,
            'lead_status'          => 'new',
            'assigned_senior_code' => $seniorCode,
        ]);
    }

    /**
     * Senior user nesnesini alır — assignedStudentIds() lower(senior_email) üzerinden arar.
     */
    private function makeStudent(User $senior, string $studentId = 'STU-001'): array
    {
        $user = User::query()->create([
            'name'       => 'Student Test',
            'email'      => "student_{$studentId}@test.local",
            'password'   => Hash::make('Secret123!'),
            'role'       => User::ROLE_STUDENT,
            'student_id' => $studentId,
            'is_active'  => true,
        ]);

        StudentAssignment::query()->create([
            'student_id'   => $studentId,
            'senior_code'  => $senior->senior_code,
            'senior_email' => $senior->email,
            'status'       => 'active',
        ]);

        return [$user, $studentId];
    }

    // ── 1. Temel sayfa yüklemeleri ────────────────────────────────────────

    public function test_senior_dashboard_loads(): void
    {
        $senior = $this->makeSenior();

        $this->actingAs($senior)
            ->get('/senior/dashboard')
            ->assertOk();
    }

    public function test_senior_student_list_loads(): void
    {
        $senior = $this->makeSenior();
        $this->makeStudent($senior);

        $this->actingAs($senior)
            ->get('/senior/students')
            ->assertOk();
    }

    public function test_senior_student_detail_loads(): void
    {
        $senior = $this->makeSenior();
        [, $studentId] = $this->makeStudent($senior);

        $this->actingAs($senior)
            ->get("/senior/students/{$studentId}")
            ->assertOk();
    }

    public function test_senior_guest_pipeline_loads(): void
    {
        $senior = $this->makeSenior();
        $this->makeGuest($senior->senior_code);

        $this->actingAs($senior)
            ->get('/senior/guest-pipeline')
            ->assertOk();
    }

    public function test_senior_student_pipeline_loads(): void
    {
        $senior = $this->makeSenior();
        $this->makeStudent($senior);

        $this->actingAs($senior)
            ->get('/senior/student-pipeline')
            ->assertOk();
    }

    // ── 2. Guest pipeline taşıma ──────────────────────────────────────────

    public function test_senior_can_move_guest_to_next_pipeline_stage(): void
    {
        $senior = $this->makeSenior();
        $guest  = $this->makeGuest($senior->senior_code);

        $this->actingAs($senior)
            ->patchJson("/senior/guest-pipeline/{$guest->id}/move", [
                'stage' => 'contacted',
            ])
            ->assertOk();

        $guest->refresh();
        $this->assertSame('contacted', $guest->lead_status);
    }

    // ── 3. Batch review sayfası ───────────────────────────────────────────

    public function test_senior_batch_review_page_loads(): void
    {
        $senior = $this->makeSenior();
        [, $studentId] = $this->makeStudent($senior);

        $category = DocumentCategory::query()->firstOrCreate(
            ['code' => 'passport'],
            ['name' => 'Pasaport', 'is_required' => true]
        );

        Document::query()->create([
            'student_id'         => $studentId,
            'category_id'        => $category->id,
            'original_file_name' => 'passport.pdf',
            'standard_file_name' => 'passport.pdf',
            'storage_path'       => 'documents/passport.pdf',
            'status'             => 'pending',
            'uploaded_by'        => 'student',
        ]);

        $this->actingAs($senior)
            ->get('/senior/batch-review')
            ->assertOk();
    }

    public function test_senior_can_approve_document_in_batch_review(): void
    {
        $senior = $this->makeSenior();
        [, $studentId] = $this->makeStudent($senior);

        $category = DocumentCategory::query()->firstOrCreate(
            ['code' => 'diploma'],
            ['name' => 'Diploma', 'is_required' => true]
        );

        $doc = Document::query()->create([
            'student_id'         => $studentId,
            'category_id'        => $category->id,
            'original_file_name' => 'diploma.pdf',
            'standard_file_name' => 'diploma.pdf',
            'storage_path'       => 'documents/diploma.pdf',
            'status'             => 'pending',
            'uploaded_by'        => 'student',
        ]);

        $this->actingAs($senior)
            ->postJson("/senior/batch-review/{$doc->id}/action", ['action' => 'approve'])
            ->assertOk();

        $doc->refresh();
        $this->assertSame('approved', $doc->status);
    }

    public function test_senior_can_reject_document_with_reason(): void
    {
        $senior = $this->makeSenior();
        [, $studentId] = $this->makeStudent($senior);

        $category = DocumentCategory::query()->firstOrCreate(
            ['code' => 'transcript'],
            ['name' => 'Transkript', 'is_required' => true]
        );

        $doc = Document::query()->create([
            'student_id'         => $studentId,
            'category_id'        => $category->id,
            'original_file_name' => 'transcript.pdf',
            'standard_file_name' => 'transcript.pdf',
            'storage_path'       => 'documents/transcript.pdf',
            'status'             => 'pending',
            'uploaded_by'        => 'student',
        ]);

        $this->actingAs($senior)
            ->postJson("/senior/batch-review/{$doc->id}/action", [
                'action' => 'reject',
                'note'   => 'Belge okunamıyor, lütfen yeniden yükleyin.',
            ])
            ->assertOk();

        $doc->refresh();
        $this->assertSame('rejected', $doc->status);
        $this->assertNotEmpty($doc->review_note ?? '');
    }

    // ── 4. Yetki izolasyonu ───────────────────────────────────────────────

    public function test_senior_cannot_see_another_seniors_student_detail(): void
    {
        $seniorA = $this->makeSenior('seniorA@test.local', 'SNR-A');
        $seniorB = $this->makeSenior('seniorB@test.local', 'SNR-B');

        // B'nin öğrencisi
        [, $studentIdB] = $this->makeStudent($seniorB, 'STU-B-001');

        // A bu öğrenciye erişemez — 403 ya da redirect kabul edilebilir
        $response = $this->actingAs($seniorA)
            ->get("/senior/students/{$studentIdB}");

        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302,
            "Beklenen 403 veya 302, alınan: {$response->status()}"
        );
    }

    public function test_senior_pipeline_poll_returns_json(): void
    {
        $senior = $this->makeSenior();
        $this->makeGuest($senior->senior_code);

        $this->actingAs($senior)
            ->getJson('/senior/guest-pipeline/poll')
            ->assertOk()
            ->assertJsonStructure(['*' => ['id']]);
    }

    // ── 5. Giriş yapmadan erişim engelleniyor ─────────────────────────────

    public function test_unauthenticated_cannot_access_senior_routes(): void
    {
        $this->get('/senior/dashboard')->assertRedirect('/login');
        $this->get('/senior/students')->assertRedirect('/login');
        $this->get('/senior/guest-pipeline')->assertRedirect('/login');
    }

    // ── 6. Guest rolü senior sayfasına giremez ────────────────────────────

    public function test_guest_role_cannot_access_senior_routes(): void
    {
        $guestUser = User::query()->create([
            'name'      => 'Guest User',
            'email'     => 'guest_role@test.local',
            'password'  => Hash::make('Secret123!'),
            'role'      => User::ROLE_GUEST,
            'is_active' => true,
        ]);

        $response = $this->actingAs($guestUser)
            ->get('/senior/dashboard');

        // EnsureSeniorRole middleware — 403 ya da redirect
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302,
            "Beklenen 403 veya 302, alınan: {$response->status()}"
        );
    }
}
