<?php

namespace App\Http\Services\Project;

use App\Exceptions\BusinessException;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ProjectMembersService {
    
    public function list(int $projectId, User $authUser): Collection {
        $project = Project::with('members')->findOrFail($projectId);

        $isMember = $project->members()->where('users.id', $authUser->id)->exists();

        if (! $isMember) {
            throw new BusinessException('Apenas membros do projeto podem visualizar os membros.', 403);
        }

        return $project->members;
    }

    public function remove(int $projectId, int $userId, User $authUser): void {
        $project = Project::findOrFail($projectId);

        $this->ensureOwner($project, $authUser);

        if ((int) $project->owner_id === (int) $userId) {
            throw new BusinessException('O dono do projeto não pode ser removido.');
        }

        $isMember = $project->members()->where('users.id', $userId)->exists();

        if (! $isMember) {
            throw new BusinessException('O usuário informado não faz parte do projeto.');
        }

        $project->members()->detach($userId);
    }

    public function leave(int $projectId, User $authUser): void {
        $project = Project::findOrFail($projectId);

        if ((int) $project->owner_id === (int) $authUser->id) {
            throw new BusinessException('O dono do projeto não pode sair do projeto.');
        }

        $isMember = $project->members()->where('users.id', $authUser->id)->exists();

        if (! $isMember) {
            throw new BusinessException('O usuário autenticado não faz parte do projeto.');
        }

        $project->members()->detach($authUser->id);
    }

    private function ensureOwner(Project $project, User $user): void {
        if ((int) $project->owner_id !== (int) $user->id) {
            throw new BusinessException('Apenas o dono do projeto pode realizar esta ação.', 403);
        }
    }
}
