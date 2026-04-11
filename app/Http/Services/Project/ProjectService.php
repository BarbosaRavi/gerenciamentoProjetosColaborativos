<?php

namespace App\Http\Services\Project;

use App\Exceptions\BusinessException;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectService {

    public function create(array $data, User $user): Project {
        return DB::transaction(function () use ($data, $user): Project {
            $project = Project::create([
                'name' => $data['name'],
                'owner_id' => $user->id,
            ]);

            $project->members()->attach($user->id);

            return $project->load('owner', 'members');
        });
    }

    public function show(int $projectId): Project {
        return Project::with(['owner', 'members', 'tasks', 'invitations'])->findOrFail($projectId);
    }

    public function update(int $projectId, array $data, User $user): Project {
        $project = Project::findOrFail($projectId);

        $this->ensureOwner($project, $user);

        $project->update([
            'name' => $data['name'],
        ]);

        return $project->load('owner', 'members');
    }

    public function delete(int $projectId, User $user): void {
        DB::transaction(function () use ($projectId, $user) :void {
            $project = Project::with(['members', 'tasks', 'invitations'])->findOrFail($projectId);
            
            $this->ensureOwner($project, $user);

            if ($project->members()->count() > 1) {
                throw new BusinessException('Não é possivel excluir projetos enquanto ele tiver membros.');
            }

            $project->invitations()->where('status', 'pending')->delete();
            $project->tasks()->delete();
            $project->members()->detach();
            $project->delete();
        });
    }

    private function ensureOwner(Project $project, User $user): void {
        if ((int) $project->owner_id !== (int) $user->id) {
            throw new BusinessException('Apenas o dono do projeto pode realizar esta ação.', 403);
        }
    }
}