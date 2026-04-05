<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_student_cannot_open_student_portal(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager',
            'email' => 'manager_access@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->get('/student/dashboard')
            ->assertForbidden();
    }

    public function test_student_cannot_open_manager_config_page(): void
    {
        $student = User::query()->create([
            'name' => 'Student',
            'email' => 'student_access@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_STUDENT,
            'student_id' => 'BCS-26-02-ACC1',
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->get('/config')
            ->assertForbidden();
    }
}

