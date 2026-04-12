<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\LeaveProjectRequest;
use App\Http\Requests\Project\RemoveProjectMemberRequest;
use App\Http\Resources\UserResource;
use App\Http\Services\Project\ProjectMembersService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProjectMemberController extends Controller
{
    public function __construct(
        private readonly ProjectMembersService $projectMemberService,
    ) {}

    public function index(int $projectId): JsonResponse
    {
        $members = $this->projectMemberService->list($projectId, auth('api')->user());

        return ApiResponse::success(
            'Membros do projeto encontrados com sucesso.',
            UserResource::collection($members),
        );
    }

    public function remove(RemoveProjectMemberRequest $request, int $projectId): JsonResponse
    {
        $this->projectMemberService->remove(
            $projectId,
            $request->validated()['user_id'],
            auth('api')->user(),
        );

        return ApiResponse::success('Membro removido com sucesso.');
    }

    public function leave(LeaveProjectRequest $request, int $projectId): JsonResponse
    {
        $this->projectMemberService->leave($projectId, auth('api')->user());

        return ApiResponse::success('Saída do projeto realizada com sucesso.');
    }
}
