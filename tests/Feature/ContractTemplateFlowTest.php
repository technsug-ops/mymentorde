<?php

namespace Tests\Feature;

use App\Models\ContractTemplate;
use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContractTemplateFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_open_contract_template_screen_and_update_student_services(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager Contract',
            'email' => 'manager_contract_template@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'company_id' => 1,
            'is_active' => true,
        ]);

        $guest = GuestApplication::query()->create([
            'company_id' => 1,
            'tracking_token' => 'TOK-CT-001',
            'first_name' => 'Ali',
            'last_name' => 'Yilmaz',
            'email' => 'ali.yilmaz@test.local',
            'application_type' => 'bachelor',
            'application_country' => 'de',
            'converted_to_student' => true,
            'converted_student_id' => 'BCS-26-02-AB12',
            'selected_package_code' => 'pkg_basic',
            'selected_package_title' => 'Basic Paket',
            'selected_package_price' => '1490 EUR',
            'selected_extra_services' => [],
        ]);

        $this->actingAs($manager)
            ->get('/manager/contract-template?q=BCS-26-02-AB12&guest_id='.$guest->id)
            ->assertOk()
            ->assertSee('Sözleşme Template Yönetimi')
            ->assertSee('BCS-26-02-AB12');

        $this->actingAs($manager)
            ->post('/manager/contract-template/student-services', [
                'guest_id' => $guest->id,
                'selected_package_title' => 'Premium Paket',
                'selected_package_price' => '3990 EUR',
                'selected_extra_services' => 'VIP Danismanlik, Konaklama Destegi',
            ])
            ->assertRedirect();

        $guest->refresh();
        $this->assertSame('Premium Paket', (string) $guest->selected_package_title);
        $this->assertSame('3990 EUR', (string) $guest->selected_package_price);
        $this->assertCount(2, (array) $guest->selected_extra_services);
    }

    public function test_manager_can_save_template_text(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager Contract',
            'email' => 'manager_contract_save@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'company_id' => 1,
            'is_active' => true,
        ]);

        $template = ContractTemplate::query()->create([
            'company_id' => 1,
            'code' => 'consultancy_v1',
            'name' => 'Danismanlik v1',
            'version' => 1,
            'is_active' => true,
            'body_text' => str_repeat('a', 120),
            'annex_kvkk_text' => 'kvkk',
            'annex_commitment_text' => 'taahhut',
            'notes' => '',
        ]);

        $this->actingAs($manager)
            ->post('/manager/contract-template', [
                'name' => 'Danismanlik v1 guncel',
                'body_text' => str_repeat('b', 120),
                'annex_kvkk_text' => 'kvkk yeni',
                'annex_commitment_text' => 'taahhut yeni',
                'notes' => 'not',
            ])
            ->assertRedirect('/manager/contract-template');

        $template->refresh();
        $this->assertSame('Danismanlik v1 guncel', (string) $template->name);
    }

    public function test_manager_can_start_contract_manually_for_selected_student(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager Contract Manual',
            'email' => 'manager_contract_manual@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'company_id' => 1,
            'is_active' => true,
        ]);

        ContractTemplate::query()->create([
            'company_id' => 1,
            'code' => 'consultancy_v1',
            'name' => 'Danismanlik v1',
            'version' => 1,
            'is_active' => true,
            'body_text' => str_repeat('x', 180),
            'annex_kvkk_text' => 'kvkk',
            'annex_commitment_text' => 'taahhut',
            'notes' => '',
        ]);

        $guest = GuestApplication::query()->create([
            'company_id' => 1,
            'tracking_token' => 'TOK-CT-002',
            'first_name' => 'Veli',
            'last_name' => 'Kaya',
            'email' => 'veli.kaya@test.local',
            'application_type' => 'bachelor',
            'application_country' => 'de',
            'converted_to_student' => true,
            'converted_student_id' => 'BCS-26-02-QA11',
            'selected_package_code' => 'pkg_plus',
            'selected_package_title' => 'Plus Paket',
            'selected_package_price' => '2490 EUR',
            'contract_status' => 'not_requested',
        ]);

        $this->actingAs($manager)
            ->post('/manager/contract-template/start-contract', [
                'guest_id' => $guest->id,
            ])
            ->assertRedirect();

        $guest->refresh();
        $this->assertSame('requested', (string) $guest->contract_status);
        $this->assertNotNull($guest->contract_requested_at);
        $this->assertNotEmpty((string) $guest->contract_snapshot_text);
        $this->assertNotEmpty((string) $guest->contract_template_code);
    }

    public function test_manager_can_approve_and_reject_contract_decision(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager Contract Decision',
            'email' => 'manager_contract_decision@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'company_id' => 1,
            'is_active' => true,
        ]);

        // forceFill(): contract_signed_at, contract_signed_file_path, contract_snapshot_text,
        // contract_template_code $fillable dışı (sistem alanları), test setup'ında forceFill gerekir.
        $guest = new GuestApplication;
        $guest->forceFill([
            'company_id' => 1,
            'tracking_token' => 'TOK-CT-003',
            'first_name' => 'Ayse',
            'last_name' => 'Yilmaz',
            'email' => 'ayse.yilmaz@test.local',
            'application_type' => 'bachelor',
            'application_country' => 'de',
            'contract_status' => 'signed_uploaded',
            'contract_requested_at' => now()->subDay(),
            'contract_signed_at' => now(),
            'contract_signed_file_path' => 'contracts/test-signed.pdf',
            'contract_snapshot_text' => str_repeat('x', 120),
            'contract_template_code' => 'consultancy_v1',
            'selected_package_code' => 'pkg_plus',
            'selected_package_title' => 'Plus Paket',
            'selected_package_price' => '2490 EUR',
        ]);
        $guest->save();

        $this->actingAs($manager)
            ->post('/manager/contract-template/decision', [
                'guest_id' => $guest->id,
                'decision' => 'approve',
                'note' => 'Kontrol edildi',
            ])
            ->assertRedirect();

        $guest->refresh();
        $this->assertSame('approved', (string) $guest->contract_status);
        $this->assertNotNull($guest->contract_approved_at);

        // Reject testi için status'u signed_uploaded'a geri al
        $guest->forceFill([
            'contract_status' => 'signed_uploaded',
            'contract_approved_at' => null,
        ])->save();

        $this->actingAs($manager)
            ->post('/manager/contract-template/decision', [
                'guest_id' => $guest->id,
                'decision' => 'reject',
                'note' => 'Tekrar imza gerekli',
            ])
            ->assertRedirect();

        $guest->refresh();
        $this->assertSame('rejected', (string) $guest->contract_status);
        $this->assertNull($guest->contract_approved_at);
    }
}
