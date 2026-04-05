<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentPortalSmokeRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_core_routes_render_successfully(): void
    {
        $student = User::query()->create([
            'name' => 'Student Smoke',
            'email' => 'student_smoke@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_STUDENT,
            'student_id' => 'BCS-26-02-SMK1',
            'is_active' => true,
        ]);

        GuestApplication::query()->create([
            'tracking_token' => 'TOK-STD-SMOKE',
            'first_name' => 'Student',
            'last_name' => 'Smoke',
            'email' => 'student_smoke@test.local',
            'application_type' => 'bachelor',
            'application_country' => 'de',
            'converted_to_student' => true,
            'converted_student_id' => 'BCS-26-02-SMK1',
            'kvkk_consent' => true,
            'docs_ready' => true,
            'registration_form_submitted_at' => now(),
            'selected_package_code' => 'pkg_basic',
            'selected_package_title' => 'Basic Paket',
            'selected_package_price' => '1490 EUR',
            'contract_status' => 'requested',
            'notifications_enabled' => true,
            'notify_email' => true,
            'notify_whatsapp' => false,
            'notify_inapp' => true,
        ]);

        $this->actingAs($student);

        $routes = [
            '/student/dashboard',
            '/student/registration',
            '/student/registration/documents',
            '/student/process-tracking',
            '/student/document-builder',
            '/student/appointments',
            '/student/tickets',
            '/student/materials',
            '/student/contract',
            '/student/services',
            '/student/vault',
            '/student/profile',
            '/student/settings',
            '/student/messages',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertOk();
        }
    }
}

