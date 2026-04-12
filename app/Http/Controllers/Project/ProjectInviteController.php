<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\SendProjectInviteRequest;
use App\Http\Resources\ProjectInvitationResource;
use App\Http\Services\Project\ProjectInviteService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProjectInviteController extends Controller {
    
    public function __construct(
        private readonly ProjectInviteService $projectInviteService,
    ) {}

    public function store(SendProjectInviteRequest $request, int $projectId): JsonResponse {
        $invitation = $this->projectInviteService->send($projectId, $request->validated(), auth('api')->user());

        return ApiResponse::success(
            'Convite enviado com sucesso.',
            new ProjectInvitationResource($invitation),
            201,
        );
    }

    public function accept(string $token): JsonResponse {
        $invitation = $this->projectInviteService->accept($token, auth('api')->user());

        return ApiResponse::success(
            'Convite aceito com sucesso.',
            new ProjectInvitationResource($invitation),
        );
    }

    public function decline(string $token): JsonResponse {
        $invitation = $this->projectInviteService->decline($token, auth('api')->user());

        return ApiResponse::success(
            'Convite recusado com sucesso.',
            new ProjectInvitationResource($invitation),
        );
    }
}