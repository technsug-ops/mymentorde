<?php

namespace Tests\Feature;

use App\Models\MarketingAdminSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_renders_and_persists_values(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        $this->actingAs($user)->get('/mktg-admin/settings')
            ->assertOk()
            ->assertSee('Panel Ayarlari');

        $this->actingAs($user)->put('/mktg-admin/settings', [
            'default_locale' => 'de',
            'default_timezone' => 'Europe/Berlin',
            'dashboard_refresh_seconds' => 45,
            'daily_summary_hour' => 11,
            'notify_on_new_lead' => 1,
            'notify_on_campaign_error' => 0,
            'brand_primary' => '#123456',
            'brand_secondary' => '#654321',
        ])->assertRedirect('/mktg-admin/settings');

        $this->assertSame('de', data_get(MarketingAdminSetting::query()->where('setting_key', 'default_locale')->first(), 'setting_value.value'));
        $this->assertSame(45, (int) data_get(MarketingAdminSetting::query()->where('setting_key', 'dashboard_refresh_seconds')->first(), 'setting_value.value'));
        $this->assertSame('#123456', data_get(MarketingAdminSetting::query()->where('setting_key', 'brand_primary')->first(), 'setting_value.value'));
    }
}

