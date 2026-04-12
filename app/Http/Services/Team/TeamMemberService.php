<?php

namespace App\Http\Services\Team;

use App\Exceptions\BusinessException;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TeamMemberService
{
    public function list(int $teamId, User $authUser): Collection {
        $team = Team::with('members')->findOrFail($teamId);

        $isMember = $team->members()->where('users.id', $authUser->id)->exists();

        if (! $isMember) {
            throw new BusinessException('Apenas membros do time podem visualizar os membros.', 403);
        }

        return $team->members;
    }

    public function remove(int $teamId, int $userId, User $authUser): void {
        $team = Team::findOrFail($teamId);

        $this->ensureOwner($team, $authUser);

        if ((int) $team->owner_id === (int) $userId) {
            throw new BusinessException('O dono do time não pode ser removido.');
        }

        $isMember = $team->members()->where('users.id', $userId)->exists();

        if (! $isMember) {
            throw new BusinessException('O usuário informado não faz parte do time.');
        }

        $team->members()->detach($userId);
    }

    public function leave(int $teamId, User $authUser): void {
        $team = Team::findOrFail($teamId);

        if ((int) $team->owner_id === (int) $authUser->id) {
            throw new BusinessException('O dono do time não pode sair do time.');
        }

        $isMember = $team->members()->where('users.id', $authUser->id)->exists();

        if (! $isMember) {
            throw new BusinessException('O usuário autenticado não faz parte do time.');
        }

        $team->members()->detach($authUser->id);
    }

    private function ensureOwner(Team $team, User $user): void {
        if ((int) $team->owner_id !== (int) $user->id) {
            throw new BusinessException('Apenas o dono do time pode realizar esta ação.', 403);
        }
    }
}
