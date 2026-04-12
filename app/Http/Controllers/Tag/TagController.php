<?php

namespace App\Http\Controllers\Tag;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Http\Services\Tag\TagService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function __construct(
        private readonly TagService $tagService,
    ) {}

    public function store(StoreTagRequest $request, int $projectId): JsonResponse
    {
        $tag = $this->tagService->create(
            $projectId,
            $request->validated(),
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Tag criada com sucesso.',
            new TagResource($tag),
            201,
        );
    }

    public function index(int $projectId): JsonResponse
    {
        $tags = $this->tagService->listByProject($projectId, auth('api')->user());

        return ApiResponse::success(
            'Tags encontradas com sucesso.',
            TagResource::collection($tags),
        );
    }

    public function update(UpdateTagRequest $request, int $tagId): JsonResponse
    {
        $tag = $this->tagService->update(
            $tagId,
            $request->validated(),
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Tag atualizada com sucesso.',
            new TagResource($tag),
        );
    }

    public function destroy(int $tagId): JsonResponse
    {
        $this->tagService->delete($tagId, auth('api')->user());

        return ApiResponse::success('Tag excluída com sucesso.');
    }
}
