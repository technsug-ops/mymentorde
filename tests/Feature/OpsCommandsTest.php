<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\NotificationDispatch;
use App\Models\StudentAssignment;
use App\Models\StudentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpsCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mvp_smoke_command_passes_with_minimum_required_data(): void
    {
        Company::query()->create(['name' => 'Test Company', 'code' => 'test', 'is_active' => true]);

        User::factory()->create([
            'role' => 'manager',
            'is_active' => true,
            'email' => 'manager@test.local',
        ]);

        User::factory()->create([
            'role' => 'senior',
            'is_active' => true,
            'auto_assign_enabled' => true,
            'max_capacity' => null,
            'email' => 'senior@test.local',
        ]);

        StudentType::query()->create([
            'name_tr' => 'Bachelor',
            'name_de' => 'Bachelor',
            'name_en' => 'Bachelor',
            'code' => 'bachelor',
            'id_prefix' => 'BCS',
            'is_active' => true,
        ]);

        $this->artisan('mvp:smoke')
            ->expectsOutputToContain('MVP smoke SONUC: PASS')
            ->assertExitCode(0);
    }

    public function test_ops_self_heal_retries_failed_notifications_and_auto_assigns_students(): void
    {
        User::factory()->create([
            'role' => 'manager',
            'is_active' => true,
            'email' => 'manager@test.local',
        ]);

        $senior = User::factory()->create([
            'role' => 'senior',
            'is_active' => true,
            'auto_assign_enabled' => true,
            'max_capacity' => 10,
            'email' => 'senior@test.local',
        ]);

        NotificationDispatch::query()->create([
            'channel' => 'email',
            'category' => 'test',
            'student_id' => 'BCS-26-02-TST1',
            'recipient_email' => 'student@test.local',
            'subject' => 'Test',
            'body' => 'Test body',
            'status' => 'failed',
            'failed_at' => now(),
            'fail_reason' => 'forced for test',
        ]);

        StudentAssignment::query()->create([
            'student_id' => 'BCS-26-02-UA01',
            'internal_sequence' => 1,
            'senior_email' => null,
            'branch' => 'istanbul',
            'risk_level' => 'normal',
            'payment_status' => 'ok',
            'is_archived' => false,
        ]);

        $this->artisan('ops:self-heal --limit=20')
            ->expectsOutputToContain('ops:self-heal SONUC: PASS')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('notification_dispatches', [
            'status' => 'failed',
            'student_id' => 'BCS-26-02-TST1',
        ]);

        $this->assertDatabaseHas('student_assignments', [
            'student_id' => 'BCS-26-02-UA01',
            'senior_email' => $senior->email,
        ]);
    }
}

