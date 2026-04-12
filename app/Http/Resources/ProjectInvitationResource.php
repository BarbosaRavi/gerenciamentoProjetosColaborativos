<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectInvitationResource extends JsonResource {

    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'email' => $this->email,
            'status' => $this->status,
            'token' => $this->token,
            'invited_by' => $this->invited_by,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'inviter' => new UserResource($this->whenLoaded('inviter')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
