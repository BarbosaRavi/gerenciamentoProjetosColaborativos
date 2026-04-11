<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Services\Project\ProjectService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller {

    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function store(StoreProjectRequest $request): JsonResponse {
        $project = $this->projectService->create($request->validated(), auth('api')->user());

        return ApiResponse::success(
            'Projeto criado com sucesso.',
            new ProjectResource($project),
            201,
        );
    }

    public function show(int $projectId): JsonResponse {
        $project = $this->projectService->show($projectId);

        return ApiResponse::success(
            'Projeto encontrado com sucesso.',
            new ProjectResource($project),
        );
    }

    public function update(UpdateProjectRequest $request, int $projectId): JsonResponse {
        $project = $this->projectService->update($projectId, $request->validated(), auth('api')->user());

        return ApiResponse::success(
            'Projeto atualizado com sucesso.',
            new ProjectResource($project),
        );
    }

    public function destroy(int $projectId): JsonResponse {
       $this->projectService->delete($projectId, auth('api')->user());

       return ApiResponse::success('Projecto excluido com sucesso.');
    }

}