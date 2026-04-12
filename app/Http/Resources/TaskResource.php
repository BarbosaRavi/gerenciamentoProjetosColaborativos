<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource {

    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'project_id' => $this->project_id,
            'created_by' => $this->created_by,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'assignees' => UserResource::collection($this->whenLoaded('assignees')),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'project_id' => $tag->project_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ];
                });
            }),
            'comments_count' => $this->whenCounted('comments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
