<?php

namespace Tests\Feature;

use App\Models\Marketing\MarketingBudget;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingBudgetPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_page_store_update_and_period_filter_work(): void
    {
        $user = User::factory()->create([
            'role' => 'marketing_admin',
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        MarketingCampaign::query()->create([
            'name' => 'IG Winter 2026',
            'channel' => 'instagram_ads',
            'status' => 'active',
            'budget' => 5000,
            'spent_amount' => 1500,
            'currency' => 'EUR',
            'start_date' => '2026-02-10',
        ]);

        $this->actingAs($user)->post('/mktg-admin/budget', [
            'period' => '2026-02',
            'total_budget' => 6000,
            'currency' => 'EUR',
            'allocations_json' => '{"instagram_ads":2500,"google_ads":2000}',
        ])->assertRedirect('/mktg-admin/budget?period=2026-02');

        $this->assertDatabaseHas('marketing_budget', [
            'period' => '2026-02',
            'currency' => 'EUR',
        ]);

        $row = MarketingBudget::query()->where('period', '2026-02')->first();
        $this->assertNotNull($row);
        $this->assertEquals(1500.0, (float) $row->total_spent);
        $this->assertEquals(4500.0, (float) $row->total_remaining);

        $this->actingAs($user)->get('/mktg-admin/budget')
            ->assertOk()
            ->assertSee('Butce Yonetimi')
            ->assertSee('2026-02');

        $this->actingAs($user)->put('/mktg-admin/budget/2026-02', [
            'period' => '2026-02',
            'total_budget' => 7000,
            'currency' => 'EUR',
            'allocations_json' => '{"instagram_ads":3000,"google_ads":2500}',
        ])->assertRedirect('/mktg-admin/budget?period=2026-02');

        $this->assertDatabaseHas('marketing_budget', [
            'period' => '2026-02',
            'total_budget' => 7000,
            'total_spent' => 1500,
            'total_remaining' => 5500,
        ]);

        $this->actingAs($user)->get('/mktg-admin/budget/2026-02')
            ->assertRedirect('/mktg-admin/budget?period=2026-02');
    }
}
