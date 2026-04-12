<?php

use App\Http\Controllers\Tag\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (): void {
    Route::post('/projects/{projectId}/tags', [TagController::class, 'store']);
    Route::get('/projects/{projectId}/tags', [TagController::class, 'index']);
    Route::put('/tags/{tagId}', [TagController::class, 'update']);
    Route::delete('/tags/{tagId}', [TagController::class, 'destroy']);
});
