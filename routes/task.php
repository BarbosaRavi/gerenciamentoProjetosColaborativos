<?php

use App\Http\Controllers\Task\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (): void {
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{projectId}/tasks', [TaskController::class, 'store']);

    Route::prefix('tasks')->group(function (): void {
        Route::get('/{taskId}', [TaskController::class, 'show']);
        Route::put('/{taskId}', [TaskController::class, 'update']);
        Route::patch('/{taskId}/status', [TaskController::class, 'updateStatus']);
        Route::delete('/{taskId}', [TaskController::class, 'destroy']);

        Route::post('/{taskId}/members', [TaskController::class, 'assignMember']);
        Route::delete('/{taskId}/members', [TaskController::class, 'removeMember']);

        Route::post('/{taskId}/tags', [TaskController::class, 'attachTag']);
        Route::delete('/{taskId}/tags', [TaskController::class, 'detachTag']);
    });
});
