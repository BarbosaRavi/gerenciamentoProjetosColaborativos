<?php

use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Project\ProjectInviteController;
use App\Http\Controllers\Project\ProjectMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('projects')->group(function (): void {
    Route::post('/', [ProjectController::class, 'store']);
    Route::get('/{projectId}', [ProjectController::class, 'show']);
    Route::put('/{projectId}', [ProjectController::class, 'update']);
    Route::delete('/{projectId}', [ProjectController::class, 'destroy']);

    Route::post('/{projectId}/invites', [ProjectInviteController::class, 'store']);
    Route::post('/invites/{token}/accept', [ProjectInviteController::class, 'accept']);
    Route::post('/invites/{token}/decline', [ProjectInviteController::class, 'decline']);

    Route::get('/{projectId}/members', [ProjectMemberController::class, 'index']);
    Route::delete('/{projectId}/members', [ProjectMemberController::class, 'remove']);
    Route::post('/{projectId}/leave', [ProjectMemberController::class, 'leave']);
});
