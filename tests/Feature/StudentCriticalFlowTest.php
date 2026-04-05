<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\MarketingTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentCriticalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_registration_page_loads(): void
    {
        $student = User::query()->create([
            'name' => 'Student User',
            'email' => 'student_flow@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_STUDENT,
            'student_id' => 'BCS-26-02-TST1',
            'is_active' => true,
        ]);

        GuestApplication::query()->create([
            'tracking_token' => 'TOK-STD-001',
            'first_name' => 'Student',
            'last_name' => 'Flow',
            'email' => 'student_flow@test.local',
            'application_type' => 'bachelor',
            'converted_to_student' => true,
            'converted_student_id' => 'BCS-26-02-TST1',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'contract_status' => 'not_requested',
        ]);

        $this->actingAs($student)
            ->get('/student/registration')
            ->assertOk()
            ->assertSee('student/registration');
    }

    public function test_student_contract_request_is_blocked_in_student_panel(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager',
            'email' => 'manager_student_contract@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'company_id' => 1,
            'is_active' => true,
        ]);

        $student = User::query()->create([
            'name' => 'Student User',
            'email' => 'student_contract@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_STUDENT,
            'student_id' => 'BCS-26-02-TST2',
            'company_id' => 1,
            'is_active' => true,
        ]);

        $guest = GuestApplication::query()->create([
            'tracking_token' => 'TOK-STD-002',
            'first_name' => 'Student',
            'last_name' => 'Contract',
            'email' => 'student_contract@test.local',
            'application_type' => 'bachelor',
            'converted_to_student' => true,
            'converted_student_id' => 'BCS-26-02-TST2',
            'kvkk_consent' => true,
            'docs_ready' => true,
            'registration_form_submitted_at' => now(),
            'selected_package_code' => 'pkg_basic',
            'selected_package_title' => 'Basic Paket',
            'selected_package_price' => '1490 EUR',
            'contract_status' => 'not_requested',
            'company_id' => 1,
        ]);

        $this->actingAs($student)
            ->post('/student/contract/request')
            ->assertRedirect('/student/contract')
            ->assertSessionHasErrors('contract');

        $guest->refresh();
        $this->assertSame('not_requested', (string) $guest->contract_status);
    }

    public function test_student_ticket_can_redirect_back_to_services(): void
    {
        $student = User::query()->create([
            'name' => 'Student User',
            'email' => 'student_ticket@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_STUDENT,
            'student_id' => 'BCS-26-02-TST3',
            'is_active' => true,
        ]);

        GuestApplication::query()->create([
            'tracking_token' => 'TOK-STD-003',
            'first_name' => 'Student',
            'last_name' => 'Ticket',
            'email' => 'student_ticket@test.local',
            'application_type' => 'bachelor',
            'converted_to_student' => true,
            'converted_student_id' => 'BCS-26-02-TST3',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'contract_status' => 'not_requested',
        ]);

        $this->actingAs($student)
            ->post('/student/tickets', [
                'subject' => 'Servis yardim',
                'message' => 'Paket secimi icin destek istiyorum.',
                'priority' => 'normal',
                'department' => 'advisory',
                'return_to' => '/student/services',
            ])
            ->assertRedirect('/student/services')
            ->assertSessionHas('status');
    }

    public function test_student_service_update_creates_auto_task(): void
    {
        User::query()->create([
            'name' => 'Manager',
            'email' => 'manager_service_task@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'company_id' => 1,
            'is_active' => true,
        ]);

        $student = User::query()->create([
            'name' => 'Student User',
            'email' => 'student_service_task@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_STUDENT,
            'student_id' => 'BCS-26-02-TST4',
            'company_id' => 1,
            'is_active' => true,
        ]);

        $guest = GuestApplication::query()->create([
            'tracking_token' => 'TOK-STD-004',
            'first_name' => 'Student',
            'last_name' => 'Service',
            'email' => 'student_service_task@test.local',
            'application_type' => 'bachelor',
            'converted_to_student' => true,
            'converted_student_id' => 'BCS-26-02-TST4',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'contract_status' => 'not_requested',
            'company_id' => 1,
        ]);

        $this->actingAs($student)
            ->post('/student/services/select-package', [
                'package_code' => 'pkg_plus',
                'package_title' => 'Plus Paket',
                'package_price' => '2490 EUR',
            ])
            ->assertRedirect('/student/services')
            ->assertSessionHas('status');

        $task = MarketingTask::query()
            ->where('source_type', 'student_service_update')
            ->where('description', 'like', '%BCS-26-02-TST4%')
            ->latest('id')
            ->first();

        $this->assertNotNull($task);

        $guest->refresh();
        $this->assertSame('pkg_plus', (string) $guest->selected_package_code);
    }
}
