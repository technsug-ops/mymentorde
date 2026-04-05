<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TicketRequestCenterCriticalTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_route_rejects_assignee_from_wrong_department_role(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager',
            'email' => 'manager_ticket_center@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $marketingUser = User::query()->create([
            'name' => 'Marketing User',
            'email' => 'marketing_wrong_dept@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MARKETING_STAFF,
            'is_active' => true,
        ]);

        $guest = GuestApplication::query()->create([
            'tracking_token' => 'TOK-GST-TKT-001',
            'first_name' => 'Guest',
            'last_name' => 'Ticket',
            'email' => 'guest_ticket_center@test.local',
            'application_type' => 'bachelor',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'converted_to_student' => false,
            'contract_status' => 'not_requested',
        ]);

        $ticket = GuestTicket::query()->create([
            'guest_application_id' => (int) $guest->id,
            'subject' => 'Finans sorusu',
            'message' => 'Odeme takvimi istiyorum',
            'status' => 'open',
            'priority' => 'normal',
            'department' => 'finance',
            'created_by_email' => 'guest_ticket_center@test.local',
        ]);

        $this->actingAs($manager)
            ->from('/tickets-center')
            ->post("/tickets-center/{$ticket->id}/route", [
                'current_department' => '',
                'department' => 'finance',
                'assignee_email' => $marketingUser->email,
                'status' => 'open',
            ])
            ->assertRedirect('/tickets-center')
            ->assertSessionHasErrors(['assignee_email']);

        $ticket->refresh();
        $this->assertNull($ticket->assigned_user_id);
        $this->assertSame('finance', (string) $ticket->department);
    }

    public function test_non_manager_request_cannot_target_non_manager_user(): void
    {
        $requester = User::query()->create([
            'name' => 'Senior User',
            'email' => 'senior_requester@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_SENIOR,
            'is_active' => true,
        ]);

        User::query()->create([
            'name' => 'Valid Manager',
            'email' => 'valid_manager@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $nonManagerTarget = User::query()->create([
            'name' => 'Wrong Target',
            'email' => 'wrong_target@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_FINANCE_STAFF,
            'is_active' => true,
        ]);

        $this->actingAs($requester)
            ->from('/manager/requests')
            ->post('/manager/requests', [
                'request_type' => 'finance',
                'subject' => 'Finans onay talebi',
                'description' => 'Onay gerekir',
                'priority' => 'normal',
                'target_manager_id' => (int) $nonManagerTarget->id,
            ])
            ->assertRedirect('/manager/requests')
            ->assertSessionHasErrors(['target_manager_id']);
    }
}

