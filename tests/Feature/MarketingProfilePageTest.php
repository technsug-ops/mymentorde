<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MarketingProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_and_updates_name_and_password(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING_ADMIN,
            'is_active' => true,
            'email' => 'marketing-admin@mentorde.local',
            'password' => bcrypt('OldPass123!'),
        ]);

        $this->actingAs($user)->get('/mktg-admin/profile')
            ->assertOk()
            ->assertSee('Hesap Bilgileri')
            ->assertSee('Kullanım Kılavuzu');

        $this->actingAs($user)->put('/mktg-admin/profile', [
            'name' => 'Marketing Admin Updated',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])->assertRedirect('/mktg-admin/profile');

        $user->refresh();
        $this->assertSame('Marketing Admin Updated', (string) $user->name);
        $this->assertTrue(Hash::check('NewPass123!', (string) $user->password));
    }
}

