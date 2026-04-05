<?php

namespace Tests\Feature;

use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingCampaignManagementPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_management_crud_and_status_actions_work(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        $this->actingAs($admin)->post('/mktg-admin/campaigns', [
            'name' => 'Winter 2026 Campaign',
            'description' => 'Aciklama',
            'channel' => 'instagram_ads',
            'budget' => 1200,
            'currency' => 'EUR',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'status' => 'draft',
            'metrics' => [
                'impressions' => 12000,
                'clicks' => 320,
            ],
        ])->assertRedirect('/mktg-admin/campaigns');

        $campaign = MarketingCampaign::query()->where('name', 'Winter 2026 Campaign')->first();
        $this->assertNotNull($campaign);

        $this->actingAs($admin)->put('/mktg-admin/campaigns/'.$campaign->id, [
            'name' => 'Winter 2026 Campaign Updated',
            'status' => 'active',
            'budget' => 1500,
        ])->assertRedirect('/mktg-admin/campaigns');

        $this->assertDatabaseHas('marketing_campaigns', [
            'id' => $campaign->id,
            'name' => 'Winter 2026 Campaign Updated',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->put('/mktg-admin/campaigns/'.$campaign->id.'/pause')
            ->assertRedirect('/mktg-admin/campaigns');
        $this->assertDatabaseHas('marketing_campaigns', [
            'id' => $campaign->id,
            'status' => 'paused',
        ]);

        $this->actingAs($admin)->put('/mktg-admin/campaigns/'.$campaign->id.'/resume')
            ->assertRedirect('/mktg-admin/campaigns');
        $this->assertDatabaseHas('marketing_campaigns', [
            'id' => $campaign->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get('/mktg-admin/campaigns')
            ->assertOk()
            ->assertSee('Kampanyalar')
            ->assertSee('Winter 2026 Campaign Updated');

        $this->actingAs($admin)->delete('/mktg-admin/campaigns/'.$campaign->id)
            ->assertRedirect('/mktg-admin/campaigns');

        $this->assertDatabaseMissing('marketing_campaigns', [
            'id' => $campaign->id,
        ]);
    }
}

