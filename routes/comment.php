<?php

use App\Http\Controllers\Comment\CommentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (): void {
    Route::post('/tasks/{taskId}/comments', [CommentController::class, 'store']);
    Route::get('/tasks/{taskId}/comments', [CommentController::class, 'index']);
    Route::put('/comments/{commentId}', [CommentController::class, 'update']);
    Route::delete('/comments/{commentId}', [CommentController::class, 'destroy']);
});
