<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller {
    
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse {
        $result = $this->authService->register($request->validated());

        return ApiResponse::token(
            'Usuário cadastrado com sucesso.', $result['token'],
            new UserResource($result['user']),
            auth('api')->factory()->getTTL() * 60, 201,
        );
    }

    public function login(LoginRequest $request): JsonResponse {
        $result = $this->authService->login($request->validated());

        return ApiResponse::token (
            'Login realizado com sucesso.', $result['token'],
            new UserResource($result['user']),
            auth('api')->factory()->getTTL() * 60,
        );
    }

    public function me(): JsonResponse {
        return ApiResponse::success(
            'Usuário autenticado.',
            new UserResource($this ->authService->me()),
        );
    }

    public function logout(): JsonResponse {
        $this->authService->logout();

        return ApiResponse::success('Logout realizado com sucesso.');
    }

    public function refresh(): JsonResponse {
        $result = $this->authService->refresh();

        return ApiResponse::token(
            'Token renovado com sucesso.', $result['token'],
            new UserResource($result['user']),
            auth('api')->factory()->getTTL() * 60,
        );
    }
}