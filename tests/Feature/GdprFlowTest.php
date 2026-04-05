<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\ManagerRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GdprFlowTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function makeStudent(string $sfx): User
    {
        $user = User::query()->create([
            'name'       => 'Student GDPR',
            'email'      => "student_gdpr_{$sfx}@test.local",
            'password'   => Hash::make('Secret123!'),
            'role'       => User::ROLE_STUDENT,
            'student_id' => "GDPR-STU-{$sfx}",
            'is_active'  => true,
        ]);

        // forceFill(): converted_to_student/converted_student_id $fillable dışı
        // (sistem tarafından set edilir), test setup'ında forceFill gerekir.
        (new GuestApplication)->forceFill([
            'tracking_token'       => "TOK-GDPR-S{$sfx}",
            'first_name'           => 'Student',
            'last_name'            => 'GDPR',
            'email'                => "student_gdpr_{$sfx}@test.local",
            'application_type'     => 'bachelor',
            'converted_to_student' => true,
            'converted_student_id' => "GDPR-STU-{$sfx}",
            'kvkk_consent'         => true,
            'docs_ready'           => false,
            'contract_status'      => 'not_requested',
        ])->save();

        return $user;
    }

    private function makeGuest(string $sfx): array
    {
        $user = User::query()->create([
            'name'      => 'Guest GDPR',
            'email'     => "guest_gdpr_{$sfx}@test.local",
            'password'  => Hash::make('Secret123!'),
            'role'      => User::ROLE_GUEST,
            'is_active' => true,
        ]);

        $app = GuestApplication::query()->create([
            'tracking_token'   => "TOK-GDPR-G{$sfx}",
            'first_name'       => 'Guest',
            'last_name'        => 'GDPR',
            'email'            => "guest_gdpr_{$sfx}@test.local",
            'guest_user_id'    => $user->id,
            'application_type' => 'bachelor',
            'kvkk_consent'     => true,
            'docs_ready'       => false,
            'contract_status'  => 'not_requested',
        ]);

        return [$user, $app];
    }

    // ── Student Tests ────────────────────────────────────────────────────────

    public function test_student_can_export_gdpr_data(): void
    {
        $student = $this->makeStudent('T1');

        $response = $this->actingAs($student)->get('/student/gdpr/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');

        $data = json_decode($response->streamedContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('user_profile', $data);
    }

    public function test_student_gdpr_erasure_creates_manager_request(): void
    {
        $student = $this->makeStudent('T2');

        $this->actingAs($student)
            ->post('/student/gdpr/erasure')
            ->assertRedirect();

        $this->assertDatabaseHas('manager_requests', [
            'requester_user_id' => $student->id,
            'request_type'      => 'gdpr_erasure',
            'status'            => 'pending',
        ]);
    }

    // ── Guest Tests ──────────────────────────────────────────────────────────

    public function test_guest_can_export_gdpr_data(): void
    {
        [$guestUser] = $this->makeGuest('T3');

        $response = $this->actingAs($guestUser)->get('/guest/gdpr/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');

        $data = json_decode($response->streamedContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('user_profile', $data);
    }

    public function test_guest_gdpr_erasure_creates_manager_request(): void
    {
        [$guestUser] = $this->makeGuest('T4');

        $this->actingAs($guestUser)
            ->post('/guest/gdpr/erasure')
            ->assertRedirect();

        $this->assertDatabaseHas('manager_requests', [
            'requester_user_id' => $guestUser->id,
            'request_type'      => 'gdpr_erasure',
        ]);
    }

    // ── IDOR Tests ───────────────────────────────────────────────────────────

    public function test_idor_student_cannot_access_guest_gdpr_export(): void
    {
        $student = $this->makeStudent('T5');

        // EnsureGuestRole middleware: ROLE_STUDENT → guest.promoted-to-student sayfasına yönlendirir (302)
        // Converted student'lar 403 yerine "terfi tebrik" sayfasına yönlendirilir.
        $this->actingAs($student)
            ->get('/guest/gdpr/export')
            ->assertRedirect(route('guest.promoted-to-student'));
    }

    public function test_idor_guest_cannot_access_student_gdpr_export(): void
    {
        [$guestUser] = $this->makeGuest('T6');

        // EnsureStudentRole middleware → 403 Forbidden (authenticated, wrong role)
        $this->actingAs($guestUser)
            ->get('/student/gdpr/export')
            ->assertForbidden();
    }

    // ── Throttle Test ────────────────────────────────────────────────────────

    public function test_erasure_throttle_blocks_excessive_requests(): void
    {
        $student = $this->makeStudent('T7');

        // throttle:3,60 → 3 requests allowed, 4th is 429
        // İlk 3 istek geçer (controller back() ile döner)
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($student)->post('/student/gdpr/erasure');
        }

        // 4. istek throttle'a takılır
        $this->actingAs($student)
            ->post('/student/gdpr/erasure')
            ->assertStatus(429);
    }

    // ── Auth Guard Test ──────────────────────────────────────────────────────

    public function test_unauthenticated_gdpr_export_redirects_to_login(): void
    {
        $this->get('/student/gdpr/export')
            ->assertRedirect('/login');
    }
}
