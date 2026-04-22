<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_endpoint_is_disabled(): void
    {
        $response = $this->postJson('/app-api/auth/register', [
            'first_name' => 'Muhammad',
            'last_name' => 'Irsyad',
            'email' => 'irsyad@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'department' => 'ICT',
            'cost_center' => 'KLHQ',
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Registration is managed centrally by Auth2 admin.');

        $this->assertGuest();
    }

    public function test_login_me_and_logout_flow_uses_auth2_and_syncs_local_user(): void
    {
        Http::fake([
            'https://auth2.weststar-dev.com/api/auth_login.php' => Http::response([
                'ok' => true,
                'user' => [
                    'id' => 18,
                    'email' => 'admin@example.com',
                    'first_name' => 'WorkPulse',
                    'last_name' => 'Admin',
                    'status' => 'active',
                ],
            ], 200),
        ]);

        $login = $this->postJson('/app-api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123!',
        ]);

        $login
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.auth_user_id', 18);

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $me = $this->getJson('/app-api/auth/me');
        $me
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.id', $user->id);

        $logout = $this->postJson('/app-api/auth/logout');
        $logout
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertGuest();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        Http::fake([
            'https://auth2.weststar-dev.com/api/auth_login.php' => Http::response([
                'ok' => false,
                'error' => 'Invalid email or password',
            ], 401),
        ]);

        $response = $this->postJson('/app-api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid email or password');
    }

    public function test_forgot_password_is_enumeration_safe(): void
    {
        Http::fake([
            'https://auth2.weststar-dev.com/api/auth_password_reset_request.php' => Http::response([
                'ok' => true,
            ], 200),
        ]);

        $response = $this->postJson('/app-api/auth/forgot-password', [
            'email' => 'missing@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
