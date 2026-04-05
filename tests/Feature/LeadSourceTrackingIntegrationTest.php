<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\LeadSourceDatum;
use App\Models\StudentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadSourceTrackingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_submission_creates_lead_source_tracking_row(): void
    {
        $response = $this->post('/apply', [
            'first_name' => 'Ayse',
            'last_name' => 'Yilmaz',
            'email' => 'ayse.apply@example.test',
            'phone' => '+49 1234567890',
            'application_type' => 'bachelor',
            'target_term' => '2026 Winter',
            'target_city' => 'Berlin',
            'language_level' => 'B1',
            'lead_source' => 'organic',
            'campaign_code' => '',
            'dealer_code' => 'REF-26-02-AAAA',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'WELCOME26',
            'utm_term' => 'almanya-danismanlik',
            'utm_content' => 'ad-01',
            'click_id' => 'gclid-abc123',
            'landing_url' => 'https://mentorde.app/apply?utm_source=google',
            'referrer_url' => 'https://google.com/',
            'branch' => 'istanbul',
            'notes' => 'test kaydi',
            'kvkk_consent' => '1',
            'docs_ready' => '0',
        ]);

        $response->assertRedirect();

        $guest = GuestApplication::query()->where('email', 'ayse.apply@example.test')->firstOrFail();
        $lead = LeadSourceDatum::query()->where('guest_id', (string) $guest->id)->firstOrFail();

        $this->assertSame('google', $lead->initial_source);
        $this->assertSame('google', $lead->utm_source);
        $this->assertSame('WELCOME26', $lead->utm_campaign);
        $this->assertTrue((bool) $lead->funnel_registered);
        $this->assertTrue((bool) $lead->funnel_form_completed);
        $this->assertFalse((bool) $lead->funnel_converted);
    }

    public function test_guest_conversion_marks_lead_source_as_verified_and_converted(): void
    {
        StudentType::query()->create([
            'name_tr' => 'Bachelor',
            'name_de' => 'Bachelor',
            'name_en' => 'Bachelor',
            'code' => 'bachelor',
            'id_prefix' => 'BCS',
            'is_active' => true,
            'sort_order' => 10,
            'created_by' => 'test',
        ]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'email' => 'manager@example.test',
            'is_active' => true,
        ]);
        $senior = User::factory()->create([
            'role' => 'senior',
            'email' => 'senior@example.test',
            'is_active' => true,
        ]);

        $guest = GuestApplication::query()->create([
            'tracking_token' => 'TOKCONVERT0001',
            'first_name' => 'Can',
            'last_name' => 'Demir',
            'email' => 'can@example.test',
            'phone' => '+49 1700000001',
            'application_type' => 'bachelor',
            'lead_source' => 'instagram',
            'campaign_code' => 'IG-WELCOME',
            'utm_source' => 'instagram',
            'utm_medium' => 'social',
            'utm_campaign' => 'IG-WELCOME',
            'branch' => 'istanbul',
            'kvkk_consent' => true,
            'docs_ready' => true,
            'converted_to_student' => false,
            'registration_form_submitted_at' => now()->subHours(2),
            'selected_package_code' => 'pkg_basic',
            'selected_package_title' => 'Basic Paket',
            'selected_package_price' => '1490 EUR',
            'contract_status' => 'approved',
            'contract_requested_at' => now()->subHours(2),
            'contract_signed_at' => now()->subHour(),
            'contract_signed_file_path' => 'contracts/can-demir-signed.pdf',
            'contract_snapshot_text' => str_repeat('x', 120),
            'contract_template_code' => 'consultancy_v1',
            'contract_approved_at' => now()->subMinutes(30),
        ]);

        $response = $this
            ->actingAs($manager)
            ->postJson("/api/v1/config/guest-applications/{$guest->id}/convert", [
                'senior_email' => $senior->email,
                'branch' => 'istanbul',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['guest_id', 'student_id', 'senior_email']);

        $lead = LeadSourceDatum::query()->where('guest_id', (string) $guest->id)->firstOrFail();
        $this->assertSame('instagram', $lead->initial_source);
        $this->assertSame('instagram', $lead->verified_source);
        $this->assertTrue((bool) $lead->source_match);
        $this->assertTrue((bool) $lead->funnel_converted);
        $this->assertTrue((bool) $lead->funnel_contract_signed);
    }
}
