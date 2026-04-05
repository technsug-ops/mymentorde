<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\MarketingTrackingLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingTrackingLinkFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_link_inventory_and_redirect_and_attribution_flow(): void
    {
        $user = User::factory()->create([
            'role' => 'marketing_admin',
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        $campaign = MarketingCampaign::query()->create([
            'name' => 'Meta Winter 2026',
            'channel' => 'meta_ads',
            'status' => 'active',
            'budget' => 1000,
            'spent_amount' => 300,
            'currency' => 'EUR',
        ]);

        $this->actingAs($user)
            ->post('/mktg-admin/tracking-links', [
                'title' => 'Meta Story Creative A',
                'category_code' => 'ad',
                'platform_code' => 'ig',
                'placement_code' => 's',
                'variation_no' => 3,
                'destination_path' => '/apply',
                'campaign_id' => $campaign->id,
                'campaign_code' => 'DE_WINTER_2026',
                'source_code' => 'instagram',
                'utm_source' => 'instagram',
                'utm_medium' => 'paid_social',
                'utm_campaign' => 'de_winter_2026',
                'utm_content' => 'story_a',
                'status' => 'active',
            ])
            ->assertRedirect('/mktg-admin/tracking-links');

        $row = MarketingTrackingLink::query()->first();
        $this->assertNotNull($row);
        $this->assertSame('adigs03', $row->code);

        $this->actingAs($user)
            ->get('/mktg-admin/tracking-links')
            ->assertOk()
            ->assertSee('Reklam Linkleri')
            ->assertSee((string) $row->code);

        $redirectResponse = $this->get('/go/'.$row->code.'?fbclid=fb-test-001');
        $redirectResponse->assertRedirect();
        $location = (string) $redirectResponse->headers->get('Location');
        $this->assertStringContainsString('/apply', $location);
        $this->assertStringContainsString('utm_source=instagram', $location);
        $this->assertStringContainsString('trk='.$row->code, $location);
        $this->assertStringContainsString('fbclid=fb-test-001', $location);

        $this->post('/apply', [
            'first_name' => 'Ali',
            'last_name' => 'Yilmaz',
            'email' => 'ali@example.com',
            'phone' => '+49 5551234567',
            'application_type' => 'bachelor',
            'lead_source' => 'organic',
            'campaign_code' => 'DE_WINTER_2026',
            'tracking_link_code' => $row->code,
            'utm_source' => 'instagram',
            'utm_medium' => 'paid_social',
            'utm_campaign' => 'de_winter_2026',
            'utm_content' => 'story_a',
            'kvkk_consent' => '1',
        ])->assertRedirect();

        $guest = GuestApplication::query()->where('email', 'ali@example.com')->first();
        $this->assertNotNull($guest);
        $this->assertSame($row->code, $guest->tracking_link_code);

        $lead = LeadSourceDatum::query()->where('guest_id', (string) $guest->id)->first();
        $this->assertNotNull($lead);
        $this->assertSame($row->code, $lead->referral_link_id);
        $this->assertSame('instagram', $lead->initial_source);
    }
}
