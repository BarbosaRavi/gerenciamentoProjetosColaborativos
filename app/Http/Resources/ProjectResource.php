<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource {

    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'owner_id' => $this->owner_id,
            'team_id' => $this->team_id,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'team' => new TeamResource($this->whenLoaded('team')),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
