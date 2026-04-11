<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithJwtApi;
use Tests\TestCase;

class AuthTest extends TestCase {
    use InteractsWithJwtApi;
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();

        $this->bootApiAuth();
    }

    public function test_register_creates_user_and_returns_token_payload(): void {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.user.email', 'john@example.com')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => ['id', 'name', 'email', 'roles'],
                ],
            ]);

            $user = User::query()->where('email', 'john@example.com')->firstOrFail();

            $this->assertTrue($user->hasRole('user'));
    }

    public function test_login_returns_token_payload_for_valid_credentials(): void {
        $user = $this->createUserWithRole('user', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',            
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonStructure([
                'data' => ['access_token', 'token_type', 'expires_in', 'user'],
            ]);
    }

    public function test_login_returns_401_invalid_credentials(): void {
        $user = $this->createUserWithRole('user', [
            'email' => 'peterpan@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'WrongPassword1223!',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['email'],
            ]);
    }

    public function test_me_returns_authenticated_user(): void {
        $user = $this->createUserWithRole('user', [
            'name' => 'Wendy',
            'email' => 'wendy@example.com',
        ]);

        $response = $this
            ->withHeaders($this->authHeadersFor($user))->getJson('/api/auth/me');

        $response   
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'wendy@example.com');
    }

    public function test_logou_returns_success_for_authenticated_user(): void {
        $user = $this->createUserWithRole('user');

        $this->withHeaders($this->authHeadersFor($user))
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_refresh_returns_new_token_payload(): void
    {
        $user = $this->createUserWithRole('user');

        $response = $this
            ->withHeaders($this->authHeadersFor($user))
            ->postJson('/api/auth/refresh');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonStructure([
                'data' => ['access_token', 'token_type', 'expires_in', 'user'],
            ]);
    }
}