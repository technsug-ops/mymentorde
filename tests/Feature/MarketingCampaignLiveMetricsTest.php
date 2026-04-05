<?php

namespace Tests\Feature;

use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingCampaignLiveMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_index_and_report_show_live_tracked_metrics(): void
    {
        $user = User::factory()->create([
            'role' => 'marketing_admin',
            'is_active' => true,
        ]);

        $campaign = MarketingCampaign::query()->create([
            'name' => 'Welcome Campaign',
            'channel' => 'google_ads',
            'status' => 'active',
            'budget' => 1000,
            'spent_amount' => 300,
            'currency' => 'EUR',
            'metrics' => [
                'impressions' => 2000,
                'clicks' => 100,
            ],
            'utm_params' => [
                'utm_campaign' => 'WELCOME26',
            ],
        ]);

        $lead1 = LeadSourceDatum::query()->create([
            'guest_id' => 'ls-1',
            'campaign_id' => $campaign->id,
            'initial_source' => 'google',
            'verified_source' => 'google',
            'source_match' => true,
            'funnel_converted' => true,
        ]);
        $lead1->timestamps = false;
        $lead1->created_at = Carbon::parse('2026-02-10 09:00:00');
        $lead1->updated_at = Carbon::parse('2026-02-10 09:00:00');
        $lead1->save();

        $lead2 = LeadSourceDatum::query()->create([
            'guest_id' => 'ls-2',
            'campaign_id' => null,
            'initial_source' => 'google',
            'verified_source' => null,
            'utm_campaign' => 'WELCOME26',
            'funnel_converted' => false,
        ]);
        $lead2->timestamps = false;
        $lead2->created_at = Carbon::parse('2026-02-10 10:00:00');
        $lead2->updated_at = Carbon::parse('2026-02-10 10:00:00');
        $lead2->save();

        $lead3 = LeadSourceDatum::query()->create([
            'guest_id' => 'ls-3',
            'campaign_id' => null,
            'initial_source' => 'google',
            'verified_source' => 'google',
            'utm_campaign' => null,
            'initial_source_detail' => 'WELCOME26',
            'funnel_converted' => false,
        ]);
        $lead3->timestamps = false;
        $lead3->created_at = Carbon::parse('2026-02-11 11:00:00');
        $lead3->updated_at = Carbon::parse('2026-02-11 11:00:00');
        $lead3->save();

        $lead4 = LeadSourceDatum::query()->create([
            'guest_id' => 'ls-4',
            'campaign_id' => null,
            'initial_source' => 'instagram',
            'verified_source' => null,
            'utm_campaign' => 'OTHER26',
        ]);
        $lead4->timestamps = false;
        $lead4->created_at = Carbon::parse('2026-02-11 11:00:00');
        $lead4->updated_at = Carbon::parse('2026-02-11 11:00:00');
        $lead4->save();

        $this->actingAs($user)
            ->get('/mktg-admin/campaigns')
            ->assertOk()
            ->assertSee('Welcome Campaign')
            ->assertSee('2,000 / 100', false)
            ->assertSee('3 / 2 / 1', false);

        $this->actingAs($user)
            ->get("/mktg-admin/campaigns/{$campaign->id}/report")
            ->assertOk()
            ->assertSee('Lead')
            ->assertSee('3', false)
            ->assertSee('2026-02-10')
            ->assertSee('2026-02-11');

        $dailyResponse = $this->actingAs($user)->getJson("/mktg-admin/campaigns/{$campaign->id}/daily-metrics");
        $dailyResponse->assertOk()
            ->assertJsonPath('campaign_id', $campaign->id);
        $daily = $dailyResponse->json('daily_metrics');
        $this->assertCount(2, $daily);
    }
}
