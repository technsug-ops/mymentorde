<?php

namespace Tests\Feature;

use App\Models\Marketing\SocialMediaAccount;
use App\Models\Marketing\SocialMediaPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingSocialModulePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_module_accounts_posts_metrics_and_calendar_work(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        $this->actingAs($admin)->get('/mktg-admin/social/accounts')
            ->assertOk()
            ->assertSee('Sosyal Hesaplar');

        $this->actingAs($admin)->post('/mktg-admin/social/accounts', [
            'platform' => 'instagram',
            'account_name' => 'MentorDE IG',
            'account_url' => 'https://instagram.com/mentorde',
            'followers' => 1200,
            'followers_growth_this_month' => 50,
            'api_connected' => 1,
            'is_active' => 1,
        ])->assertRedirect('/mktg-admin/social/accounts');

        $account = SocialMediaAccount::query()->where('account_name', 'MentorDE IG')->first();
        $this->assertNotNull($account);

        $this->actingAs($admin)->get('/mktg-admin/social/posts')
            ->assertOk()
            ->assertSee('Sosyal Gonderiler');

        $this->actingAs($admin)->post('/mktg-admin/social/posts', [
            'account_id' => $account->id,
            'platform' => 'instagram',
            'caption' => 'Yeni donem basvurulari acildi',
            'media_urls' => 'https://example.com/1.jpg,https://example.com/2.jpg',
            'post_type' => 'reel',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(1)->toDateTimeString(),
            'metric_views' => 1000,
            'metric_likes' => 100,
            'metric_comments' => 12,
            'metric_shares' => 7,
            'metric_saves' => 5,
        ])->assertRedirect('/mktg-admin/social/posts');

        $post = SocialMediaPost::query()->where('account_id', $account->id)->first();
        $this->assertNotNull($post);

        $this->actingAs($admin)->put('/mktg-admin/social/posts/'.$post->id.'/publish')
            ->assertRedirect('/mktg-admin/social/posts');
        $this->assertDatabaseHas('social_media_posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);

        $this->actingAs($admin)->put('/mktg-admin/social/posts/'.$post->id.'/metrics', [
            'metric_likes' => 150,
            'metric_comments' => 18,
            'metric_shares' => 9,
            'metric_click_through' => 33,
            'metric_guest_registrations' => 4,
        ])->assertRedirect('/mktg-admin/social/posts');

        $this->assertDatabaseHas('social_media_posts', [
            'id' => $post->id,
            'metric_likes' => 150,
            'metric_guest_registrations' => 4,
        ]);

        $period = now()->format('Y-m');
        $this->actingAs($admin)->get('/mktg-admin/social/metrics?period='.$period)
            ->assertOk()
            ->assertSee('Sosyal Medya Metrikleri')
            ->assertSee('MentorDE IG');

        $this->actingAs($admin)->get('/mktg-admin/social/metrics/monthly/'.$period)
            ->assertOk()
            ->assertSee('Aylik Sosyal Medya');

        $this->actingAs($admin)->get('/mktg-admin/social/calendar?month='.$period)
            ->assertOk()
            ->assertSee('Sosyal Medya Takvimi');
    }
}

