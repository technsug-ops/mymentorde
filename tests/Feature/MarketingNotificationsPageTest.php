<?php

namespace Tests\Feature;

use App\Models\NotificationDispatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingNotificationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_notifications_screen_and_actions_work(): void
    {
        $user = User::factory()->create([
            'role' => 'marketing_admin',
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        $queuedWithRecipient = NotificationDispatch::query()->create([
            'channel' => 'email',
            'category' => 'email_campaign',
            'student_id' => 'BCS100001',
            'recipient_email' => 'student1@example.com',
            'body' => 'welcome-1',
            'status' => 'queued',
            'queued_at' => now(),
            'user_id' => $user->id,
        ]);

        $queuedWithoutRecipient = NotificationDispatch::query()->create([
            'channel' => 'email',
            'category' => 'welcome',
            'student_id' => 'BCS100002',
            'recipient_email' => null,
            'recipient_phone' => null,
            'body' => 'welcome-2',
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        $failed = NotificationDispatch::query()->create([
            'channel' => 'whatsapp',
            'category' => 'reminder',
            'student_id' => 'BCS100003',
            'recipient_phone' => '+491700000000',
            'body' => 'reminder',
            'status' => 'failed',
            'failed_at' => now(),
            'fail_reason' => 'provider down',
        ]);

        $this->actingAs($user)->get('/mktg-admin/notifications')
            ->assertOk()
            ->assertSee('Bildirimler')
            ->assertSee('BCS100001');

        $this->actingAs($user)->post('/mktg-admin/notifications/dispatch-now', [
            'limit' => 10,
        ])->assertRedirect('/mktg-admin/notifications');

        $this->assertDatabaseHas('notification_dispatches', [
            'id' => $queuedWithRecipient->id,
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('notification_dispatches', [
            'id' => $queuedWithoutRecipient->id,
            'status' => 'failed',
            'fail_reason' => 'recipient missing',
        ]);

        $this->actingAs($user)->post('/mktg-admin/notifications/retry-failed', [
            'limit' => 10,
        ])->assertRedirect('/mktg-admin/notifications');

        $this->assertDatabaseHas('notification_dispatches', [
            'id' => $failed->id,
            'status' => 'queued',
        ]);

        $this->actingAs($user)->post('/mktg-admin/notifications/'.$failed->id.'/mark-sent')
            ->assertRedirect('/mktg-admin/notifications');
        $this->assertDatabaseHas('notification_dispatches', [
            'id' => $failed->id,
            'status' => 'sent',
        ]);

        $this->actingAs($user)->post('/mktg-admin/notifications/'.$failed->id.'/mark-failed', [
            'reason' => 'manual-check-fail',
        ])->assertRedirect('/mktg-admin/notifications');
        $this->assertDatabaseHas('notification_dispatches', [
            'id' => $failed->id,
            'status' => 'failed',
            'fail_reason' => 'manual-check-fail',
        ]);
    }
}

