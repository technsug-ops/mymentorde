<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Models\GuestApplication;
use App\Models\MarketingCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApplySuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_apply_suggestions_returns_ranked_lists_without_auth(): void
    {
        Dealer::query()->create([
            'code' => 'REF-26-02-AAAA',
            'internal_sequence' => 1,
            'name' => 'Ref A',
            'dealer_type_code' => 'referrer',
            'is_active' => true,
            'is_archived' => false,
        ]);

        MarketingCampaign::query()->create([
            'name' => 'Spring Intake',
            'channel' => 'email',
            'status' => 'draft',
            'utm_params' => [
                'campaign_code' => 'SPRING-CODE-26',
            ],
        ]);

        GuestApplication::query()->create([
            'tracking_token' => 'TOK001ABCDEF',
            'first_name' => 'Ali',
            'last_name' => 'Yilmaz',
            'email' => 'ali@example.test',
            'application_type' => 'bachelor',
            'lead_source' => 'organic',
            'dealer_code' => 'REF-26-02-AAAA',
            'campaign_code' => 'SPRING26',
            'branch' => 'istanbul',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'converted_to_student' => false,
        ]);

        GuestApplication::query()->create([
            'tracking_token' => 'TOK002ABCDEF',
            'first_name' => 'Ayse',
            'last_name' => 'Demir',
            'email' => 'ayse@example.test',
            'application_type' => 'master',
            'lead_source' => 'instagram',
            'dealer_code' => 'REF-26-02-AAAA',
            'campaign_code' => 'SPRING26',
            'branch' => 'istanbul',
            'kvkk_consent' => true,
            'docs_ready' => true,
            'converted_to_student' => false,
        ]);

        $response = $this->getJson('/api/v1/public/apply-suggestions?limit=20');

        $response->assertOk()
            ->assertJsonStructure([
                'dealer_codes',
                'campaign_values',
                'branch_values',
                'meta' => ['window_months', 'strategy'],
            ])
            ->assertJsonFragment(['window_months' => 12])
            ->assertJsonPath('meta.strategy', 'recent_then_popular_then_active');

        $payload = $response->json();
        $this->assertContains('REF-26-02-AAAA', $payload['dealer_codes']);
        $this->assertContains('SPRING26', $payload['campaign_values']);
        $this->assertContains('Spring Intake', $payload['campaign_values']);
        $this->assertContains('SPRING-CODE-26', $payload['campaign_values']);
        $this->assertContains('istanbul', $payload['branch_values']);
    }

    public function test_public_lead_source_options_endpoint_returns_active_options_without_auth(): void
    {
        $response = $this->getJson('/api/v1/public/lead-source-options');

        $response->assertOk();
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('code', $data[0]);
        $this->assertArrayHasKey('label', $data[0]);
    }
}
