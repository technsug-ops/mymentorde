<?php

namespace Tests\Feature;

use App\Models\MarketingTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingTeamPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_page_invite_update_and_remove_flow_works(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        $this->actingAs($admin)->get('/mktg-admin/team')
            ->assertOk()
            ->assertSee('Ekip Yonetimi');

        $this->actingAs($admin)->post('/mktg-admin/team/invite', [
            'name' => 'Sales Staff 1',
            'email' => 'sales.staff1@mentorde.local',
            'role' => User::ROLE_SALES_STAFF,
            'permissions' => ['marketing.dashboard.view'],
        ])->assertRedirect('/mktg-admin/team');

        $invited = User::query()->where('email', 'sales.staff1@mentorde.local')->first();
        $this->assertNotNull($invited);
        $this->assertSame(User::ROLE_SALES_STAFF, (string) $invited->role);

        $team = MarketingTeam::query()->where('user_id', $invited->id)->first();
        $this->assertNotNull($team);
        $this->assertSame(User::ROLE_SALES_STAFF, (string) $team->role);
        $this->assertSame(['marketing.dashboard.view'], array_values((array) $team->permissions));

        $this->actingAs($admin)->put('/mktg-admin/team/'.$invited->id.'/permissions', [
            'role' => User::ROLE_MARKETING_STAFF,
            'permissions' => ['marketing.dashboard.view', 'marketing.campaign.manage'],
        ])->assertRedirect('/mktg-admin/team');

        $invited->refresh();
        $team->refresh();
        $this->assertSame(User::ROLE_MARKETING_STAFF, (string) $invited->role);
        $this->assertSame(User::ROLE_MARKETING_STAFF, (string) $team->role);
        $this->assertSame(
            ['marketing.dashboard.view', 'marketing.campaign.manage'],
            array_values((array) $team->permissions)
        );

        $this->actingAs($admin)->delete('/mktg-admin/team/'.$invited->id)
            ->assertRedirect('/mktg-admin/team');

        $this->assertDatabaseMissing('marketing_teams', ['user_id' => $invited->id]);
        $this->assertSame(User::ROLE_STUDENT, (string) $invited->fresh()->role);
    }
}

