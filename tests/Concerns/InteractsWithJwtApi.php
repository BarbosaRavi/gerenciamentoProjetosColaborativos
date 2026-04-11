<?php

namespace Tests\Concerns;

use App\Models\User;
use Database\Seeders\PermissionsAndRoles;

trait InteractsWithJwtApi
{
    protected function bootApiAuth(): void
    {
        $this->seed(PermissionsAndRoles::class);
    }

    protected function createUserWithRole(string $role = 'trainer', array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'password' => 'Password123!',
        ], $attributes));

        $user->assignRole($role);

        return $user->fresh();
    }

    protected function authHeadersFor(User $user): array
    {
        $token = auth('api')->login($user);

        return [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ];
    }
}
