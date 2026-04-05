<?php

namespace Tests\Feature;

use App\Models\Marketing\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingEmailTemplatesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_templates_screen_crud_and_test_send_work(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
        ]);

        $this->actingAs($user)->get('/mktg-admin/email/templates')
            ->assertOk()
            ->assertSee('Template Listesi');

        $this->actingAs($user)->post('/mktg-admin/email/templates', [
            'name' => 'Hos Geldin',
            'type' => 'manual',
            'category' => 'welcome',
            'subject_tr' => 'MentorDE Hos Geldiniz',
            'body_tr' => 'Merhaba {{student_name}}',
            'placeholders' => 'student_name,senior_name',
            'from_name' => 'MentorDE',
            'from_email' => 'noreply@mentorde.com',
            'is_active' => '1',
        ])->assertRedirect('/mktg-admin/email/templates');

        $template = EmailTemplate::query()->where('name', 'Hos Geldin')->first();
        $this->assertNotNull($template);
        $this->assertSame((int) $user->id, (int) $template->created_by);

        $this->actingAs($user)->post('/mktg-admin/email/templates/'.$template->id.'/test-send')
            ->assertRedirect('/mktg-admin/email/templates');
        $this->assertDatabaseHas('email_send_log', [
            'template_id' => $template->id,
            'status' => 'sent',
            'recipient_email' => $user->email,
        ]);

        $this->actingAs($user)->put('/mktg-admin/email/templates/'.$template->id, [
            'name' => 'Hos Geldin v2',
            'is_active' => '0',
        ])->assertRedirect('/mktg-admin/email/templates');

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'name' => 'Hos Geldin v2',
            'is_active' => 0,
        ]);

        $this->actingAs($user)->delete('/mktg-admin/email/templates/'.$template->id)
            ->assertRedirect('/mktg-admin/email/templates');
        $this->assertDatabaseMissing('email_templates', ['id' => $template->id]);
    }
}

