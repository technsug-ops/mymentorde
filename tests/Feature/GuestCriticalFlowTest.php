<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\GuestRequiredDocument;
use App\Models\GuestTicket;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestCriticalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_contract_request_and_signed_upload_respect_flow_guards(): void
    {
        Storage::fake('local');

        $user = User::query()->create([
            'name' => 'Guest User',
            'email' => 'guest_flow@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_GUEST,
            'is_active' => true,
        ]);

        $guest = GuestApplication::query()->create([
            'tracking_token' => 'TOK-GST-001',
            'first_name' => 'Guest',
            'last_name' => 'Flow',
            'email' => 'guest_flow@test.local',
            'application_type' => 'bachelor',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'converted_to_student' => false,
            'contract_status' => 'not_requested',
        ]);

        $this->actingAs($user)
            ->post('/guest/contract/request')
            ->assertRedirect('/guest/contract')
            ->assertSessionHasErrors('contract');

        $guest->forceFill([
            'registration_form_submitted_at' => now(),
            'docs_ready' => true,
            'selected_package_code' => 'pkg_basic',
            'selected_package_title' => 'Basic Paket',
            'selected_package_price' => '1490 EUR',
        ])->save();

        $this->actingAs($user)
            ->post('/guest/contract/request')
            ->assertRedirect('/guest/contract')
            ->assertSessionHas('status');

        $guest->refresh();
        // Guest talebi → 'pending_manager': manager snapshot üretene kadar bekler
        $this->assertSame('pending_manager', (string) $guest->contract_status);

        // Upload-signed 'pending_manager' durumunda reddedilmeli
        $pdf = UploadedFile::fake()->create('signed.pdf', 128, 'application/pdf');
        $this->actingAs($user)
            ->post('/guest/contract/upload-signed', ['signed_contract' => $pdf])
            ->assertRedirect('/guest/contract')
            ->assertSessionHasErrors('contract');

        // Manager snapshot ürettikten sonra durum 'requested' olur — bunu simüle et
        $guest->forceFill([
            'contract_status' => 'requested',
            'contract_snapshot_text' => 'Sözleşme metni örnek',
            'contract_template_code' => 'TPL-TEST-001',
            'contract_requested_at' => now(),
        ])->save();

        $pdf = UploadedFile::fake()->create('signed.pdf', 128, 'application/pdf');
        $this->actingAs($user)
            ->post('/guest/contract/upload-signed', ['signed_contract' => $pdf])
            ->assertRedirect();

        $guest->refresh();
        $this->assertSame('signed_uploaded', (string) $guest->contract_status);
        $this->assertNotEmpty((string) $guest->contract_signed_file_path);
        Storage::disk('local')->assertExists((string) $guest->contract_signed_file_path);
    }

    public function test_guest_ticket_auto_department_routing_works(): void
    {
        $user = User::query()->create([
            'name' => 'Guest User',
            'email' => 'guest_ticket@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_GUEST,
            'is_active' => true,
        ]);

        GuestApplication::query()->create([
            'tracking_token' => 'TOK-GST-002',
            'first_name' => 'Guest',
            'last_name' => 'Ticket',
            'email' => 'guest_ticket@test.local',
            'application_type' => 'bachelor',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'converted_to_student' => false,
            'contract_status' => 'not_requested',
        ]);

        $this->actingAs($user)
            ->post('/guest/tickets', [
                'subject' => 'Odeme ve fatura sorusu',
                'message' => 'Fatura ve odeme plani hakkinda bilgi istiyorum.',
                'priority' => 'normal',
                'department' => 'auto',
            ])
            ->assertRedirect('/guest/tickets');

        $ticket = GuestTicket::query()->latest('id')->first();
        $this->assertNotNull($ticket);
        $this->assertSame('finance', (string) $ticket->department);
    }

    public function test_guest_profile_page_loads_without_runtime_error(): void
    {
        $user = User::query()->create([
            'name' => 'Guest User',
            'email' => 'guest_profile@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_GUEST,
            'is_active' => true,
        ]);

        GuestApplication::query()->create([
            'tracking_token' => 'TOK-GST-003',
            'first_name' => 'Guest',
            'last_name' => 'Profile',
            'email' => 'guest_profile@test.local',
            'application_type' => 'bachelor',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'converted_to_student' => false,
            'contract_status' => 'not_requested',
        ]);

        $this->actingAs($user)
            ->get('/guest/profile')
            ->assertOk()
            ->assertSee('Profilim');
    }

    public function test_guest_required_document_upload_updates_docs_ready_and_document_record(): void
    {
        Storage::fake('local');

        $user = User::query()->create([
            'name' => 'Guest User',
            'email' => 'guest_docs@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_GUEST,
            'is_active' => true,
        ]);

        $guest = GuestApplication::query()->create([
            'tracking_token' => 'TOK-GST-004',
            'first_name' => 'Guest',
            'last_name' => 'Docs',
            'email' => 'guest_docs@test.local',
            'application_type' => 'test_type_docs',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'converted_to_student' => false,
            'contract_status' => 'not_requested',
        ]);

        DocumentCategory::query()->firstOrCreate(
            ['code' => 'DOC-PASS'],
            [
                'name_tr' => 'Pasaport',
                'top_category_code' => 'kisisel_dokumanlar',
                'is_active' => true,
                'sort_order' => 10,
            ]
        );

        GuestRequiredDocument::query()->firstOrCreate(
            [
                'company_id' => 1,
                'application_type' => 'test_type_docs',
                'document_code' => 'DOC-PASS',
            ],
            [
                'category_code' => 'DOC-PASS',
                'name' => 'Pasaport',
                'description' => 'Pasaport ilk 2 sayfa',
                'is_required' => true,
                'accepted' => 'pdf,jpg,png',
                'max_mb' => 10,
                'sort_order' => 10,
                'is_active' => true,
            ]
        );

        $file = UploadedFile::fake()->create('passport.pdf', 256, 'application/pdf');

        $this->actingAs($user)
            ->post('/guest/registration/documents/upload', [
                'category_code' => 'DOC-PASS',
                'file' => $file,
            ])
            ->assertRedirect('/guest/registration/documents')
            ->assertSessionHas('status');

        $guest->refresh();
        $this->assertTrue((bool) $guest->docs_ready);

        $doc = Document::query()->latest('id')->first();
        $this->assertNotNull($doc);
        $this->assertSame('uploaded', (string) $doc->status);
        $this->assertStringContainsString('DOC-PASS', (string) $doc->standard_file_name);
        Storage::disk('local')->assertExists((string) $doc->storage_path);
    }

    public function test_guest_conversion_readiness_endpoint_returns_missing_checklist(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager User',
            'email' => 'manager_readiness@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $guest = GuestApplication::query()->create([
            'tracking_token' => 'TOK-GST-005',
            'first_name' => 'Guest',
            'last_name' => 'Readiness',
            'email' => 'guest_readiness@test.local',
            'application_type' => 'bachelor',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'converted_to_student' => false,
            'contract_status' => 'requested',
            'selected_package_code' => null,
            'registration_form_submitted_at' => null,
        ]);

        $response = $this->actingAs($manager)
            ->getJson("/api/v1/config/guest-applications/{$guest->id}/conversion-readiness");

        $response->assertOk()
            ->assertJsonPath('ready', false)
            ->assertJsonPath('checks.registration_form_submitted', false)
            ->assertJsonPath('checks.documents_ready', false)
            ->assertJsonPath('checks.package_selected', false)
            ->assertJsonPath('checks.contract_approved', false)
            ->assertJsonFragment(['on_kayit_formu'])
            ->assertJsonFragment(['belgeler'])
            ->assertJsonFragment(['paket_secimi'])
            ->assertJsonFragment(['sozlesme_onayi']);
    }

    public function test_guest_convert_returns_missing_details_when_not_ready(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager User',
            'email' => 'manager_convert@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $guest = GuestApplication::query()->create([
            'tracking_token' => 'TOK-GST-006',
            'first_name' => 'Guest',
            'last_name' => 'Convert',
            'email' => 'guest_convert@test.local',
            'application_type' => 'bachelor',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'converted_to_student' => false,
            'contract_status' => 'not_requested',
            'selected_package_code' => null,
            'registration_form_submitted_at' => null,
        ]);

        $response = $this->actingAs($manager)
            ->postJson("/api/v1/config/guest-applications/{$guest->id}/convert", []);

        $response->assertStatus(422)
            ->assertJsonPath('ready', false)
            ->assertJsonPath('checks.registration_form_submitted', false)
            ->assertJsonPath('checks.documents_ready', false)
            ->assertJsonPath('checks.package_selected', false)
            ->assertJsonPath('checks.contract_approved', false)
            ->assertJsonFragment(['message' => 'Donusum kosullari tamamlanmadi.']);
    }

    public function test_guest_applications_index_includes_conversion_readiness_payload(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager User',
            'email' => 'manager_index@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        GuestApplication::query()->create([
            'tracking_token' => 'TOK-GST-007',
            'first_name' => 'Guest',
            'last_name' => 'Index',
            'email' => 'guest_index@test.local',
            'application_type' => 'bachelor',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'converted_to_student' => false,
            'contract_status' => 'not_requested',
            'selected_package_code' => null,
            'registration_form_submitted_at' => null,
        ]);

        $response = $this->actingAs($manager)
            ->getJson('/api/v1/config/guest-applications?converted=false');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'conversion_ready',
                    'conversion_missing',
                    'conversion_checks' => [
                        'registration_form_submitted',
                        'documents_ready',
                        'package_selected',
                        'contract_approved',
                    ],
                ],
            ]);
    }
}
