<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'email' => $this->email,
            'status' => $this->status,
            'token' => $this->token,
            'invited_by' => $this->invited_by,
            'team' => new TeamResource($this->whenLoaded('team')),
            'inviter' => new UserResource($this->whenLoaded('inviter')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
