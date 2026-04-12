<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\LeaveTeamRequest;
use App\Http\Requests\Team\RemoveTeamMemberRequest;
use App\Http\Services\Team\TeamMemberService;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TeamMemberController extends Controller
{
    public function __construct(
        private readonly TeamMemberService $teamMemberService,
    ) {}

    public function index(int $teamId): JsonResponse {
        $members = $this->teamMemberService->list($teamId, auth('api')->user());

        return ApiResponse::success(
            'Membros do time encontrados com sucesso.',
            UserResource::collection($members),
        );
    }

    public function remove(RemoveTeamMemberRequest $request, int $teamId): JsonResponse
    {
        $this->teamMemberService->remove(
            $teamId,
            $request->validated()['user_id'],
            auth('api')->user(),
        );

        return ApiResponse::success('Membro removido com sucesso.');
    }

    public function leave(LeaveTeamRequest $request, int $teamId): JsonResponse
    {
        $this->teamMemberService->leave($teamId, auth('api')->user());

        return ApiResponse::success('Saída do time realizada com sucesso.');
    }
}
