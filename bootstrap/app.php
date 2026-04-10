<?php

use App\Exceptions\BusinessException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $shouldReturnJson = fn (Request $request): bool =>
            $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (ValidationException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Os dados informados são inválidos.',
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (BusinessException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], $exception->status());
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para executar esta ação.',
            ], 403);
        });

        $exceptions->render(function (SpatieUnauthorizedException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para executar esta ação.',
            ], 403);
        });

        $exceptions->render(function (TokenExpiredException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Token expirado.',
            ], 401);
        });

        $exceptions->render(function (TokenInvalidException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Token inválido.',
            ], 401);
        });

        $exceptions->render(function (JWTException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Token ausente ou malformado.',
            ], 401);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Recurso não encontrado.',
            ], 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Rota não encontrada.',
            ], 404);
        });

        $exceptions->render(function (QueryException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar a operação no banco de dados.',
                'errors' => app()->isLocal() ? ['database' => [$exception->getMessage()]] : null,
            ], 500);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para executar esta ação.',
            ], 403);
        });

        $exceptions->render(function (\Throwable $exception, Request $request) use ($shouldReturnJson) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor.',
                'errors' => app()->isLocal() ? ['exception' => [$exception->getMessage()]] : null,
            ], 500);
        });
    })
    ->create();
