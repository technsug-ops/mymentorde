<?php

namespace Tests\Feature;

use App\Models\Marketing\EventRegistration;
use App\Models\Marketing\MarketingEvent;
use App\Models\NotificationDispatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingEventsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_flow_create_publish_registrations_report_survey_and_reminder_works(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        $this->actingAs($admin)->get('/mktg-admin/events')
            ->assertOk()
            ->assertSee('Etkinlik Yonetimi');

        $this->actingAs($admin)->post('/mktg-admin/events', [
            'title_tr' => 'Webinar A',
            'description_tr' => 'Webinar test aciklamasi',
            'start_date' => now()->addDays(3)->toDateTimeString(),
            'end_date' => now()->addDays(3)->addHour()->toDateTimeString(),
            'timezone' => 'Europe/Berlin',
            'type' => 'webinar',
            'format' => 'online',
            'status' => 'draft',
            'capacity' => 50,
            'reminders_json' => '[{"minutesBefore":60,"channel":"email"}]',
            'post_event_survey_enabled' => 1,
            'waitlist_enabled' => 1,
        ])->assertRedirect('/mktg-admin/events');

        $event = MarketingEvent::query()->where('title_tr', 'Webinar A')->first();
        $this->assertNotNull($event);

        $this->actingAs($admin)->put('/mktg-admin/events/'.$event->id.'/publish')
            ->assertRedirect('/mktg-admin/events');
        $this->assertDatabaseHas('marketing_events', [
            'id' => $event->id,
            'status' => 'published',
        ]);

        $reg1 = EventRegistration::query()->create([
            'event_id' => $event->id,
            'user_id' => null,
            'first_name' => 'Ali',
            'last_name' => 'Test',
            'email' => 'ali.test@example.com',
            'phone' => '123',
            'role' => 'student',
            'mentorde_id' => 'BCS100001',
            'status' => 'registered',
            'survey_completed' => false,
            'survey_score' => null,
            'survey_feedback' => null,
            'converted_to_guest_after' => false,
            'converted_guest_id' => null,
            'source' => 'direct_link',
            'registered_at' => now(),
            'created_at' => now(),
        ]);

        EventRegistration::query()->create([
            'event_id' => $event->id,
            'user_id' => null,
            'first_name' => 'Ayse',
            'last_name' => 'Test',
            'email' => 'ayse.test@example.com',
            'phone' => '456',
            'role' => 'student',
            'mentorde_id' => 'BCS100002',
            'status' => 'registered',
            'survey_completed' => true,
            'survey_score' => 8,
            'survey_feedback' => 'iyi',
            'converted_to_guest_after' => false,
            'converted_guest_id' => null,
            'source' => 'social',
            'registered_at' => now(),
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->get('/mktg-admin/events/'.$event->id.'/registrations')
            ->assertOk()
            ->assertSee('Etkinlik Kayitlari')
            ->assertSee('ali.test@example.com');

        $this->actingAs($admin)->put('/mktg-admin/events/'.$event->id.'/registrations/'.$reg1->id.'/status', [
            'status' => 'attended',
            'survey_score' => 9,
            'survey_completed' => 1,
        ])->assertRedirect('/mktg-admin/events/'.$event->id.'/registrations');

        $this->assertDatabaseHas('event_registrations', [
            'id' => $reg1->id,
            'status' => 'attended',
            'survey_score' => 9,
        ]);

        $this->actingAs($admin)->post('/mktg-admin/events/'.$event->id.'/send-reminder')
            ->assertRedirect('/mktg-admin/events');
        $this->assertDatabaseCount('notification_dispatches', 1);
        $this->assertDatabaseHas('notification_dispatches', [
            'source_type' => 'marketing_event',
            'source_id' => (string) $event->id,
            'category' => 'event_reminder',
        ]);

        $this->actingAs($admin)->get('/mktg-admin/events/'.$event->id.'/report')
            ->assertOk()
            ->assertSee('Etkinlik Raporu')
            ->assertSee('Status Dagilimi');

        $this->actingAs($admin)->get('/mktg-admin/events/'.$event->id.'/survey-results')
            ->assertOk()
            ->assertSee('Anket Sonuclari')
            ->assertSee('ayse.test@example.com');

        $this->actingAs($admin)->put('/mktg-admin/events/'.$event->id.'/cancel')
            ->assertRedirect('/mktg-admin/events');
        $this->assertDatabaseHas('marketing_events', [
            'id' => $event->id,
            'status' => 'cancelled',
        ]);
    }
}

