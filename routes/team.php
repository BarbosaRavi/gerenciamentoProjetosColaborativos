<?php

use App\Http\Controllers\Team\TeamController;
use App\Http\Controllers\Team\TeamInviteController;
use App\Http\Controllers\Team\TeamMemberController;
use App\Http\Controllers\Team\TeamProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('teams')->group(function (): void {
    Route::post('/', [TeamController::class, 'store']);
    Route::get('/{teamId}', [TeamController::class, 'show']);
    Route::put('/{teamId}', [TeamController::class, 'update']);
    Route::delete('/{teamId}', [TeamController::class, 'destroy']);

    Route::post('/{teamId}/invites', [TeamInviteController::class, 'store']);
    Route::post('/invites/{token}/accept', [TeamInviteController::class, 'accept']);
    Route::post('/invites/{token}/decline', [TeamInviteController::class, 'decline']);

    Route::get('/{teamId}/members', [TeamMemberController::class, 'index']);
    Route::delete('/{teamId}/members', [TeamMemberController::class, 'remove']);
    Route::post('/{teamId}/leave', [TeamMemberController::class, 'leave']);

    Route::post('/{teamId}/projects', [TeamProjectController::class, 'store']);
});
