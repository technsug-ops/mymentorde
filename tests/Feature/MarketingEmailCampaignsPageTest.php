<?php

namespace Tests\Feature;

use App\Models\Marketing\EmailCampaign;
use App\Models\Marketing\EmailSegment;
use App\Models\Marketing\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingEmailCampaignsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_campaigns_flow_create_schedule_send_stats_and_log_works(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);
        $r1 = User::factory()->create(['email' => 'r1@example.com']);
        $r2 = User::factory()->create(['email' => 'r2@example.com']);

        $template = EmailTemplate::query()->create([
            'name' => 'Welcome TR',
            'type' => 'manual',
            'category' => 'welcome',
            'subject_tr' => 'Hos geldiniz',
            'body_tr' => 'Merhaba {{student_name}}',
            'created_by' => $admin->id,
            'is_active' => true,
            'trigger_is_active' => true,
        ]);

        $segment = EmailSegment::query()->create([
            'name' => 'Segment A',
            'description' => 'test',
            'type' => 'manual',
            'rules' => [],
            'member_user_ids' => [$r1->id, $r2->id],
            'estimated_size' => 2,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get('/mktg-admin/email/campaigns')
            ->assertOk()
            ->assertSee('Kampanya Listesi');

        $this->actingAs($admin)->post('/mktg-admin/email/campaigns', [
            'name' => 'Launch A',
            'template_id' => $template->id,
            'segment_ids' => [$segment->id],
            'status' => 'draft',
        ])->assertRedirect('/mktg-admin/email/campaigns');

        $campaign = EmailCampaign::query()->where('name', 'Launch A')->first();
        $this->assertNotNull($campaign);
        $this->assertSame(2, (int) $campaign->total_recipients);

        $this->actingAs($admin)->post('/mktg-admin/email/campaigns/'.$campaign->id.'/schedule', [
            'scheduled_at' => now()->addHour()->toDateTimeString(),
        ])->assertRedirect('/mktg-admin/email/campaigns');
        $this->assertDatabaseHas('email_campaigns', [
            'id' => $campaign->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($admin)->post('/mktg-admin/email/campaigns/'.$campaign->id.'/send')
            ->assertRedirect('/mktg-admin/email/campaigns');
        $this->assertDatabaseHas('email_campaigns', [
            'id' => $campaign->id,
            'status' => 'sent',
            'stat_sent' => 2,
        ]);
        $this->assertDatabaseCount('email_send_log', 2);

        $this->actingAs($admin)->get('/mktg-admin/email/campaigns/'.$campaign->id.'/stats')
            ->assertOk()
            ->assertSee('Kampanya Istatistikleri')
            ->assertSee('Launch A');

        $this->actingAs($admin)->get('/mktg-admin/email/log?campaign_id='.$campaign->id)
            ->assertOk()
            ->assertSee('E-posta Send Log')
            ->assertSee('r1@example.com');
    }
}

