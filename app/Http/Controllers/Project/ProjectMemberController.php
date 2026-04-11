<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\LeaveProjectRequest;
use App\Http\Requests\Project\RemoveProjectMemberRequest;
use App\Http\Services\Project\ProjectMembersService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProjectMemberController extends Controller
{
    public function __construct(
        private readonly ProjectMembersService $projectMemberService,
    ) {}

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
