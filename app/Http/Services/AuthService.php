<?php

namespace App\Http\Services;


use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthService {

    public function register(array $data): array {
        return DB::transaction(function () use ($data):array {
            $user = User::create($data);
            $user->assignRole('user');
            $token = auth('api')->login($user);

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }

    public function login(array $credentials): array {
        $token = auth('api')->attempt($credentials);
        
        if (! $token) {
            throw new BusinessException(
                'Credenciais inválidas.', 401, ['email' => ['Credenciais inválidas.']]
            );
        }     
        
        return [
            'user' => auth('api')->user(),
            'token' => $token,
        ];
    }

    public function me(): User {
        return auth('api')->user();
    }

    public function logout(): void {
        auth('api')->logout();
    }

    public function refresh(): array {
        $token = auth('api')->refresh();
        $user = auth('api')->setToken($token)->user();

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
