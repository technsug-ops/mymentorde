<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_request_returns_err_unauthorized(): void
    {
        $response = $this->getJson('/api/v1/config/suggestions');

        $response
            ->assertStatus(401)
            ->assertJson([
                'error_code' => 'ERR_UNAUTHORIZED',
                'status' => 401,
            ]);
    }

    public function test_non_manager_user_gets_err_forbidden_on_manager_api(): void
    {
        $user = User::factory()->create([
            'role' => 'senior',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/config/suggestions');

        $response
            ->assertStatus(403)
            ->assertJson([
                'error_code' => 'ERR_FORBIDDEN',
                'status' => 403,
            ]);
    }

    public function test_unknown_api_route_returns_err_not_found(): void
    {
        $response = $this->getJson('/api/v1/route-that-does-not-exist');

        $response
            ->assertStatus(404)
            ->assertJson([
                'error_code' => 'ERR_NOT_FOUND',
                'status' => 404,
            ]);
    }

    public function test_wrong_http_method_returns_err_method_not_allowed(): void
    {
        $response = $this->getJson('/api/v1/config/student-assignments/auto-assign');

        $response
            ->assertStatus(405)
            ->assertJson([
                'error_code' => 'ERR_METHOD_NOT_ALLOWED',
                'status' => 405,
            ]);
    }
}

