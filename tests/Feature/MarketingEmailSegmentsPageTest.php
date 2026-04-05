<?php

namespace Tests\Feature;

use App\Models\Marketing\EmailSegment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingEmailSegmentsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_segments_flow_create_update_preview_and_delete_works(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);
        $u1 = User::factory()->create(['role' => User::ROLE_STUDENT, 'is_active' => true, 'email' => 'seg1@example.com']);
        $u2 = User::factory()->create(['role' => User::ROLE_STUDENT, 'is_active' => true, 'email' => 'seg2@example.com']);
        User::factory()->create(['role' => User::ROLE_DEALER, 'is_active' => false, 'email' => 'seg3@example.com']);

        $this->actingAs($admin)->get('/mktg-admin/email/segments')
            ->assertOk()
            ->assertSee('Segment Listesi');

        $this->actingAs($admin)->post('/mktg-admin/email/segments', [
            'name' => 'Static Segment',
            'description' => 'manual test',
            'type' => 'manual',
            'member_user_ids' => $u1->id.','.$u2->id,
            'rules_text' => '',
            'is_active' => '1',
        ])->assertRedirect('/mktg-admin/email/segments');

        $segment = EmailSegment::query()->where('name', 'Static Segment')->first();
        $this->assertNotNull($segment);
        $this->assertSame(2, (int) $segment->estimated_size);

        $this->actingAs($admin)->put('/mktg-admin/email/segments/'.$segment->id, [
            'type' => 'dynamic',
            'rules_text' => '{"role":"student","is_active":true,"limit":1}',
            'is_active' => '1',
        ])->assertRedirect('/mktg-admin/email/segments');

        $segment->refresh();
        $this->assertSame('dynamic', (string) $segment->type);
        $this->assertSame(1, (int) $segment->estimated_size);

        $this->actingAs($admin)->get('/mktg-admin/email/segments/'.$segment->id.'/preview')
            ->assertOk()
            ->assertSee('Segment Onizleme')
            ->assertSee('seg1@example.com');

        $this->actingAs($admin)->delete('/mktg-admin/email/segments/'.$segment->id)
            ->assertRedirect('/mktg-admin/email/segments');
        $this->assertDatabaseMissing('email_segments', ['id' => $segment->id]);
    }
}

