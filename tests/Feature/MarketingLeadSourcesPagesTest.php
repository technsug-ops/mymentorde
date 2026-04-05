<?php

namespace Tests\Feature;

use App\Models\LeadSourceDatum;
use App\Models\MarketingTrackingLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingLeadSourcesPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_source_pages_render_live_data(): void
    {
        $user = User::factory()->create([
            'role' => 'marketing_admin',
            'is_active' => true,
        ]);

        LeadSourceDatum::query()->create([
            'guest_id' => 'lead-1',
            'initial_source' => 'google',
            'verified_source' => 'google',
            'source_match' => true,
            'referral_link_id' => 'adigs03',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'WELCOME26',
            'funnel_registered' => true,
            'funnel_form_completed' => true,
            'funnel_documents_uploaded' => true,
            'funnel_package_selected' => true,
            'funnel_contract_signed' => true,
            'funnel_converted' => true,
        ]);

        LeadSourceDatum::query()->create([
            'guest_id' => 'lead-2',
            'initial_source' => 'instagram',
            'verified_source' => null,
            'referral_link_id' => 'adigs03',
            'utm_source' => 'instagram',
            'utm_medium' => 'social',
            'utm_campaign' => 'IG26',
            'funnel_registered' => true,
            'funnel_form_completed' => false,
            'funnel_dropped_at_stage' => 'form_completed',
        ]);

        MarketingTrackingLink::query()->create([
            'title' => 'Instagram Story A',
            'code' => 'adigs03',
            'category_code' => 'ad',
            'platform_code' => 'ig',
            'placement_code' => 's',
            'variation_no' => 3,
            'destination_path' => '/apply',
            'campaign_code' => 'WELCOME26',
            'source_code' => 'instagram',
            'status' => 'active',
            'click_count' => 25,
        ]);

        $oldLead = LeadSourceDatum::query()->create([
            'guest_id' => 'lead-old',
            'initial_source' => 'google',
            'referral_link_id' => 'adigs99',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'OLD25',
            'funnel_registered' => true,
            'funnel_form_completed' => true,
        ]);
        $oldLead->forceFill([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ])->save();

        MarketingTrackingLink::query()->create([
            'title' => 'Old Campaign Ad',
            'code' => 'adigs99',
            'category_code' => 'ad',
            'platform_code' => 'ig',
            'placement_code' => 's',
            'variation_no' => 99,
            'destination_path' => '/apply',
            'campaign_code' => 'OLD25',
            'source_code' => 'google',
            'status' => 'paused',
            'click_count' => 13,
        ]);

        $this->actingAs($user)->get('/mktg-admin/lead-sources')
            ->assertOk()
            ->assertSee('Kaynak Özeti')
            ->assertSee('google');

        $this->actingAs($user)->get('/mktg-admin/lead-sources/funnel')
            ->assertOk()
            ->assertSee('Funnel Analizi')
            ->assertSee('registered');

        $this->actingAs($user)->get('/mktg-admin/lead-sources/utm')
            ->assertOk()
            ->assertSee('UTM Performans')
            ->assertSee('WELCOME26');

        $this->actingAs($user)->get('/mktg-admin/lead-sources/tracking-codes')
            ->assertOk()
            ->assertSee('Tracking Codes')
            ->assertSee('adigs03')
            ->assertSee('Instagram Story A');

        $today = now()->toDateString();
        $this->actingAs($user)->get('/mktg-admin/lead-sources/tracking-codes?start_date='.$today.'&end_date='.$today)
            ->assertOk()
            ->assertSee('adigs03')
            ->assertDontSee('adigs99');

        $this->actingAs($user)->get('/mktg-admin/lead-sources/tracking-codes/csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition');

        $this->actingAs($user)->get('/mktg-admin/lead-sources/dropoff')
            ->assertOk()
            ->assertSee('Explicit Dropoff')
            ->assertSee('form_completed');

        $this->actingAs($user)->get('/mktg-admin/lead-sources/source-verify')
            ->assertOk()
            ->assertSee('Mismatch');
    }
}
