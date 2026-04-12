<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\SendTeamInviteRequest;
use App\Http\Resources\TeamInvitationResource;
use App\Http\Services\Team\TeamInviteService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TeamInviteController extends Controller {

    public function __construct(
        private readonly TeamInviteService $teamInviteService,
    ) {}

    public function store(SendTeamInviteRequest $request, int $teamId): JsonResponse {
        $invitation = $this->teamInviteService->send($teamId, $request->validated(), auth('api')->user());

        return ApiResponse::success(
            'Convite enviado com sucesso.',
            new TeamInvitationResource($invitation),
            201,
        );
    }

    public function accept(string $token): JsonResponse {
        $invitation = $this->teamInviteService->accept($token, auth('api')->user());

        return ApiResponse::success(
            'Convite aceito com sucesso.',
            new TeamInvitationResource($invitation),
        );
    }

    public function decline(string $token): JsonResponse {
        $invitation = $this->teamInviteService->decline($token, auth('api')->user());

        return ApiResponse::success(
            'Convite recusado com sucesso.',
            new TeamInvitationResource($invitation),
        );
    }
}
