<?php

namespace Tests\Feature;

use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\StudentRevenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingAdminDashboardDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_live_marketing_metrics(): void
    {
        $user = User::factory()->create([
            'role' => 'marketing_admin',
            'is_active' => true,
        ]);

        LeadSourceDatum::query()->create([
            'guest_id' => 'g1',
            'initial_source' => 'google',
            'verified_source' => 'google',
            'utm_campaign' => 'WELCOME26',
        ]);
        LeadSourceDatum::query()->create([
            'guest_id' => 'g2',
            'initial_source' => 'google',
            'verified_source' => null,
            'utm_campaign' => 'WELCOME26',
        ]);
        LeadSourceDatum::query()->create([
            'guest_id' => 'g3',
            'initial_source' => 'instagram',
            'verified_source' => 'instagram',
            'utm_campaign' => 'WELCOME26',
        ]);
        LeadSourceDatum::query()->create([
            'guest_id' => 'g4',
            'initial_source' => 'organic',
            'verified_source' => null,
            'utm_campaign' => 'ORGANIC26',
        ]);

        MarketingCampaign::query()->create([
            'name' => 'Welcome',
            'channel' => 'google_ads',
            'status' => 'active',
            'budget' => 1000,
            'spent_amount' => 400,
            'currency' => 'EUR',
        ]);

        StudentRevenue::query()->create([
            'student_id' => 'BCS-26-02-TEST',
            'total_earned' => 1000,
        ]);

        $response = $this->actingAs($user)->get('/mktg-admin/dashboard');

        $response->assertOk()
            ->assertSee('Yeni Guest')
            ->assertSee('google', false);
    }
}
