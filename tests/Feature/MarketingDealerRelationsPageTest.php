<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Models\DealerStudentRevenue;
use App\Models\DealerType;
use App\Models\LeadSourceDatum;
use App\Models\MarketingTrackingLink;
use App\Models\NotificationDispatch;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingDealerRelationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dealer_relations_pages_and_actions_work(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        DealerType::query()->create([
            'name_tr' => 'Yonlendirici Bayi',
            'name_de' => 'Referrer',
            'name_en' => 'Referrer',
            'code' => 'referrer',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        Dealer::query()->create([
            'code' => 'OPE-000001',
            'name' => 'Operasyon Bayi',
            'dealer_type_code' => 'referrer',
            'is_active' => true,
            'email' => 'ope-dealer@mentorde.test',
        ]);
        Dealer::query()->create([
            'code' => 'REF-000001',
            'name' => 'Ref Bayi',
            'dealer_type_code' => 'referrer',
            'is_active' => true,
            'email' => 'ref-dealer@mentorde.test',
        ]);

        StudentAssignment::query()->create([
            'student_id' => 'BCS100001',
            'senior_email' => 'senior@mentorde.local',
            'branch' => 'istanbul',
            'risk_level' => 'normal',
            'payment_status' => 'ok',
            'dealer_id' => 'OPE-000001',
            'student_type' => 'bachelor',
            'is_archived' => false,
        ]);

        DealerStudentRevenue::query()->create([
            'dealer_id' => 'OPE-000001',
            'student_id' => 'BCS100001',
            'dealer_type' => 'referrer',
            'milestone_progress' => [['key' => 'REV-001', 'status' => 'paid']],
            'total_earned' => 500,
            'total_pending' => 250,
        ]);

        MarketingTrackingLink::query()->create([
            'title' => 'Dealer Link',
            'code' => 'adigs01',
            'category_code' => 'ad',
            'platform_code' => 'ig',
            'placement_code' => 's',
            'variation_no' => 1,
            'destination_path' => '/apply',
            'campaign_id' => null,
            'campaign_code' => 'de_winter_2026',
            'dealer_code' => 'OPE-000001',
            'source_code' => 'instagram',
            'utm_source' => 'instagram',
            'utm_medium' => 'paid_social',
            'utm_campaign' => 'de_winter_2026',
            'utm_term' => null,
            'utm_content' => 'story_a',
            'status' => 'active',
            'click_count' => 14,
            'created_by' => $admin->id,
        ]);

        LeadSourceDatum::query()->create([
            'guest_id' => 'G-1',
            'initial_source' => 'dealer',
            'campaign_id' => null,
            'dealer_id' => 'OPE-000001',
            'utm_source' => 'instagram',
            'utm_medium' => 'paid_social',
            'utm_campaign' => 'de_winter_2026',
            'funnel_registered' => true,
            'funnel_converted' => true,
            'funnel_converted_at' => now(),
        ]);

        $this->actingAs($admin)->get('/mktg-admin/dealers')
            ->assertOk()
            ->assertSee('Bayi Iliskileri')
            ->assertSee('OPE-000001');

        $this->actingAs($admin)->get('/mktg-admin/dealers/OPE-000001/performance')
            ->assertOk()
            ->assertSee('Bayi Performansi')
            ->assertSee('BCS100001')
            ->assertSee('Dealer Link');

        $this->actingAs($admin)->post('/mktg-admin/dealers/broadcast', [
            'dealer_codes' => 'OPE-000001,REF-000001',
            'channel' => 'email',
            'subject' => 'Bayi Bilgilendirme',
            'message' => 'Yeni kampanya materyalleri eklendi.',
        ])->assertRedirect('/mktg-admin/dealers');

        $this->assertSame(2, NotificationDispatch::query()->where('category', 'dealer_broadcast')->count());

        $this->actingAs($admin)->post('/mktg-admin/dealers/materials', [
            'dealer_codes' => 'OPE-000001,REF-000001',
            'material_title' => 'Yeni Brodur',
            'material_url' => 'https://example.com/materials/new-brochure.pdf',
            'material_type' => 'pdf',
            'note' => 'Bu hafta kullanilsin.',
        ])->assertRedirect('/mktg-admin/dealers');

        $this->assertSame(2, NotificationDispatch::query()->where('category', 'dealer_material')->count());
    }
}

