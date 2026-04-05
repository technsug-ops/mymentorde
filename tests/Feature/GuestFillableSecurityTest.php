<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * GuestApplication.$fillable güvenlik sınırı — mass-assignment koruması testleri.
 *
 * Kapsam:
 *  - Kritik alanlar (converted_to_student, contract_approved_at, is_archived vb.)
 *    HTTP form gönderiminden etkilenmemeli.
 *  - İzin verilen alanlar (first_name, phone vb.) guest rotalarında güncellenebilmeli.
 *
 * Mimari not:
 *  WorkflowController metotları zaten validate() ile daraltır; $fillable ikinci savunma
 *  katmanıdır. Bu testler: "validate() yanlış genişlese bile $fillable engeller" ilkesini
 *  doğrular.
 */
class GuestFillableSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ── Yardımcı ──────────────────────────────────────────────────────────────

    private function makeGuestUser(): User
    {
        return User::query()->create([
            'name'      => 'Security Test Guest',
            'email'     => 'sec_guest@test.local',
            'password'  => Hash::make('Secret123!'),
            'role'      => User::ROLE_GUEST,
            'is_active' => true,
        ]);
    }

    private function makeGuestApplication(User $user): GuestApplication
    {
        $g = new GuestApplication;
        $g->forceFill([
            'tracking_token'   => 'SEC-TOK-001',
            'email'            => $user->email,
            'first_name'       => 'Original',
            'last_name'        => 'Name',
            'application_type' => 'bachelor',
            'kvkk_consent'     => false,
            // Kritik alan başlangıç değerleri:
            'converted_to_student' => false,
            'converted_student_id' => null,
            'is_archived'          => false,
            'contract_status'      => 'not_requested',
        ]);
        $g->save();
        return $g;
    }

    // ── $fillable model düzeyinde ─────────────────────────────────────────────

    /**
     * Model::fill() kritik alanları yoksaymalı.
     */
    public function test_fill_does_not_write_converted_to_student(): void
    {
        $g = new GuestApplication;
        $g->fill([
            'email'                => 'fill@test.local',
            'converted_to_student' => true,   // fillable dışı
            'converted_student_id' => 'BCS-99',
        ]);

        $this->assertFalse((bool) $g->converted_to_student,
            'converted_to_student fill() ile yazılmamalı.');
        $this->assertNull($g->converted_student_id,
            'converted_student_id fill() ile yazılmamalı.');
    }

    public function test_fill_does_not_write_is_archived(): void
    {
        $g = new GuestApplication;
        $g->fill(['is_archived' => true]);

        $this->assertFalse((bool) $g->is_archived, 'is_archived fill() ile yazılmamalı.');
    }

    public function test_fill_does_not_write_contract_approved_at(): void
    {
        $g = new GuestApplication;
        $g->fill(['contract_approved_at' => now()->toDateTimeString()]);

        $this->assertNull($g->contract_approved_at,
            'contract_approved_at fill() ile yazılmamalı.');
    }

    public function test_fill_does_not_write_contract_signed_at(): void
    {
        $g = new GuestApplication;
        $g->fill(['contract_signed_at' => now()->toDateTimeString()]);

        $this->assertNull($g->contract_signed_at,
            'contract_signed_at fill() ile yazılmamalı.');
    }

    public function test_fill_allows_guest_editable_fields(): void
    {
        $g = new GuestApplication;
        $g->fill([
            'first_name' => 'Ayşe',
            'last_name'  => 'Kaya',
            'phone'      => '+905559876543',
            'gender'     => 'kadın',
        ]);

        $this->assertSame('Ayşe',         $g->first_name);
        $this->assertSame('Kaya',         $g->last_name);
        $this->assertSame('+905559876543',$g->phone);
        $this->assertSame('kadın',        $g->gender);
    }

    // ── Model + save() düzeyinde (fillable bütünlüğü) ─────────────────────────

    /**
     * fill() + save() → izin verilen alan DB'ye yazılmalı.
     * $fillable ikinci katmanı: meşru güncelleme engellenmemeli.
     */
    public function test_fill_and_save_writes_allowed_field_to_db(): void
    {
        $guest = new GuestApplication;
        $guest->forceFill([
            'tracking_token'   => 'SEC-SAVE-001',
            'email'            => 'saveable@test.local',
            'first_name'       => 'Original',
            'last_name'        => 'Soyad',
            'application_type' => 'bachelor',
            'kvkk_consent'     => false,
        ]);
        $guest->save();

        // fill() + save() ile izin verilen alan güncellenmeli.
        $guest->fill(['first_name' => 'Güncellendi', 'phone' => '+905001234567']);
        $guest->save();

        $fresh = GuestApplication::withoutGlobalScopes()->find($guest->id);
        $this->assertSame('Güncellendi', $fresh->first_name,
            'fill() + save() first_name\'i DB\'ye yazmalı.');
        $this->assertSame('+905001234567', $fresh->phone,
            'fill() + save() phone\'u DB\'ye yazmalı.');
    }

    /**
     * fill() kritik alan atamazsa save() de onu DB'ye yazmaz.
     */
    public function test_fill_and_save_never_writes_critical_field_to_db(): void
    {
        $guest = new GuestApplication;
        $guest->forceFill([
            'tracking_token'       => 'SEC-SAVE-002',
            'email'                => 'critical@test.local',
            'first_name'           => 'Test',
            'last_name'            => 'User',
            'application_type'     => 'bachelor',
            'kvkk_consent'         => false,
            'converted_to_student' => false,
        ]);
        $guest->save();

        // fill() kritik alanı yoksayar → save() boş dirty seti → DB değişmez.
        $guest->fill(['converted_to_student' => true, 'contract_approved_at' => now()]);
        $guest->save();

        $fresh = GuestApplication::withoutGlobalScopes()->find($guest->id);
        $this->assertFalse((bool) $fresh->converted_to_student,
            'fill() + save() converted_to_student\'ı DB\'ye yazmamalı.');
        $this->assertNull($fresh->contract_approved_at,
            'fill() + save() contract_approved_at\'ı DB\'ye yazmamalı.');
    }
}
