<?php

namespace Tests\Feature;

use App\Models\LeadSourceDatum;
use App\Models\Marketing\CmsContent;
use App\Models\Marketing\CmsMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingCmsContentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_cms_content_category_and_media_flow_works(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        $this->actingAs($admin)->get('/mktg-admin/content')
            ->assertOk()
            ->assertSee('CMS Icerik Yonetimi');

        $this->actingAs($admin)->post('/mktg-admin/categories', [
            'code' => 'blog',
            'name_tr' => 'Blog',
            'is_active' => 1,
            'sort_order' => 10,
        ])->assertRedirect('/mktg-admin/categories');

        $this->assertDatabaseHas('cms_categories', [
            'code' => 'BLOG',
            'name_tr' => 'Blog',
        ]);

        $this->actingAs($admin)->post('/mktg-admin/content', [
            'type' => 'blog',
            'slug' => 'winter-2026-guide',
            'title_tr' => 'Winter 2026 Rehberi',
            'summary_tr' => 'Kisa ozet',
            'content_tr' => 'Uzun icerik metni',
            'category' => 'BLOG',
            'status' => 'draft',
            'tags' => 'winter,guide',
            'seo_keywords' => 'almanya,egitim',
        ])->assertRedirect('/mktg-admin/content');

        $content = CmsContent::query()->where('slug', 'winter-2026-guide')->first();
        $this->assertNotNull($content);

        LeadSourceDatum::query()->create([
            'guest_id' => 'G-101',
            'initial_source' => 'organic',
            'campaign_id' => null,
            'dealer_id' => null,
            'cms_content_id' => $content->id,
            'utm_source' => 'google',
            'utm_medium' => 'organic',
            'utm_campaign' => null,
            'funnel_registered' => true,
            'funnel_converted' => true,
            'funnel_converted_at' => now(),
        ]);

        $this->actingAs($admin)->put('/mktg-admin/content/'.$content->id.'/publish')
            ->assertRedirect('/mktg-admin/content');
        $this->assertDatabaseHas('cms_contents', [
            'id' => $content->id,
            'status' => 'published',
        ]);

        $this->actingAs($admin)->put('/mktg-admin/content/'.$content->id, [
            'title_tr' => 'Winter 2026 Rehberi Guncel',
            'content_tr' => 'Guncel metin',
            'change_note' => 'headline update',
        ])->assertRedirect('/mktg-admin/content');

        $this->assertDatabaseHas('cms_contents', [
            'id' => $content->id,
            'title_tr' => 'Winter 2026 Rehberi Guncel',
        ]);

        $this->actingAs($admin)->put('/mktg-admin/content/'.$content->id.'/schedule', [
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
        ])->assertRedirect('/mktg-admin/content');
        $this->assertDatabaseHas('cms_contents', [
            'id' => $content->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($admin)->put('/mktg-admin/content/'.$content->id.'/feature', [
            'featured_order' => 5,
        ])->assertRedirect('/mktg-admin/content');
        $this->assertDatabaseHas('cms_contents', [
            'id' => $content->id,
            'is_featured' => true,
        ]);

        $this->actingAs($admin)->get('/mktg-admin/content/'.$content->id.'/stats')
            ->assertOk()
            ->assertSee('Lead Conversion');

        $this->actingAs($admin)->get('/mktg-admin/content/'.$content->id.'/revisions')
            ->assertOk()
            ->assertSee('Revizyonlar');

        $this->actingAs($admin)->post('/mktg-admin/media/upload', [
            'file_name' => 'hero.jpg',
            'file_url' => 'https://example.com/hero.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => 2048,
            'tags' => 'hero,cover',
        ])->assertRedirect('/mktg-admin/media');

        $media = CmsMedia::query()->where('file_name', 'hero.jpg')->first();
        $this->assertNotNull($media);

        $this->actingAs($admin)->delete('/mktg-admin/media/'.$media->id)
            ->assertRedirect('/mktg-admin/media');
        $this->assertDatabaseMissing('cms_media_library', ['id' => $media->id]);
    }
}

