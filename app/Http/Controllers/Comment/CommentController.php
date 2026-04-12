<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Http\Services\Comment\CommentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller {
    
    public function __construct(
        private readonly CommentService $commentService,
    ) {}

    public function store(StoreCommentRequest $request, int $taskId): JsonResponse {
        $comment = $this->commentService->create(
            $taskId,
            $request->validated(),
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Comentário criado com sucesso.',
            new CommentResource($comment),
            201,
        );
    }

    public function index(int $taskId): JsonResponse {
        $comments = $this->commentService->listByTask($taskId, auth('api')->user());

        return ApiResponse::success(
            'Comentários encontrados com sucesso.',
            CommentResource::collection($comments),
        );
    }

    public function update(UpdateCommentRequest $request, int $commentId): JsonResponse {
        $comment = $this->commentService->update(
            $commentId,
            $request->validated(),
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Comentário atualizado com sucesso.',
            new CommentResource($comment),
        );
    }

    public function destroy(int $commentId): JsonResponse {
        $this->commentService->delete($commentId, auth('api')->user());

        return ApiResponse::success('Comentário excluído com sucesso.');
    }
}
