<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Http\Services\Team\TeamService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller {
    
    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    public function store(StoreTeamRequest $request): JsonResponse {
        $team = $this->teamService->create($request->validated(), auth('api')->user());

        return ApiResponse::success(
            'Time criado com sucesso.',
            new TeamResource($team),
            201,
        );
    }

    public function show(int $teamId): JsonResponse {
        $team = $this->teamService->show($teamId);

        return ApiResponse::success(
            'Time encontrado com sucesso.',
            new TeamResource($team),
        );
    }

    public function update(UpdateTeamRequest $request, int $teamId): JsonResponse {
        $team = $this->teamService->update($teamId, $request->validated(), auth('api')->user());

        return ApiResponse::success(
            'Time atualizado com sucesso.',
            new TeamResource($team),
        );
    }

    public function destroy(int $teamId): JsonResponse {
        $this->teamService->delete($teamId, auth('api')->user());

        return ApiResponse::success('Time excluído com sucesso.');
    }
}
