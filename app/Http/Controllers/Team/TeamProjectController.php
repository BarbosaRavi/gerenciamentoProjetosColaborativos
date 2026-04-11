<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\CreateTeamProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Services\Team\TeamProjectService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TeamProjectController extends Controller
{
    public function __construct(
        private readonly TeamProjectService $teamProjectService,
    ) {}

    public function store(CreateTeamProjectRequest $request, int $teamId): JsonResponse
    {
        $project = $this->teamProjectService->create($teamId, $request->validated(), auth('api')->user());

        return ApiResponse::success(
            'Projeto do time criado com sucesso.',
            new ProjectResource($project),
            201,
        );
    }
}
