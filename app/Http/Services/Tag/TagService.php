<?php

namespace App\Http\Services\Tag;

use App\Exceptions\BusinessException;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TagService {

    public function create(int $projectId, array $data, User $user): Tag {
        return DB::transaction(function () use ($projectId, $data, $user): Tag {
            $project = Project::with('members')->findOrFail($projectId);

            $this->ensureProjectMember($project, $user);

            $slug = Str::slug($data['name']);

            $alreadyExists = $project->tags()->where('slug', $slug)->exists();

            if ($alreadyExists) {
                throw new BusinessException('Já existe uma tag com este nome neste projeto.');
            }

            $tag = $project->tags()->create([
                'name' => $data['name'],
                'slug' => $slug,
            ]);

            return $tag;
        });
    }

    public function listByProject(int $projectId, User $user): Collection {
        $project = Project::with(['members', 'tags'])->findOrFail($projectId);

        $this->ensureProjectMember($project, $user);

        return $project->tags()->orderBy('name')->get();
    }

    public function update(int $tagId, array $data, User $user): Tag {
        return DB::transaction(function () use ($tagId, $data, $user): Tag {
            $tag = Tag::with('project.members')->findOrFail($tagId);

            $this->ensureProjectMember($tag->project, $user);

            $slug = Str::slug($data['name']);

            $alreadyExists = $tag->project->tags()->where('slug', $slug)->where('id', '!=', $tag->id)->exists();

            if ($alreadyExists) {
                throw new BusinessException('Já existe uma tag com este nome neste projeto.');
            }

            $tag->update([
                'name' => $data['name'],
                'slug' => $slug,
            ]);

            return $tag->fresh();
        });
    }

    public function delete(int $tagId, User $user): void {
        DB::transaction(function () use ($tagId, $user): void {
            $tag = Tag::with('project.members')->findOrFail($tagId);

            $this->ensureProjectMember($tag->project, $user);

            $tag->tasks()->detach();
            $tag->delete();
        });
    }

    private function ensureProjectMember(Project $project, User $user): void {
        $isMember = $project->members()->where('users.id', $user->id)->exists();

        if (! $isMember) {
            throw new BusinessException('Apenas membros do projeto podem realizar esta ação.', 403);
        }
    }
}
