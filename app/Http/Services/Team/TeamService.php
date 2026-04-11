<?php

namespace App\Http\Services\Team;

use App\Exceptions\BusinessException;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamService {

    public function create(array $data, User $user): Team {
        return DB::transaction(function () use ($data, $user): Team {
            $team = Team::create([
                'name' => $data['name'],
                'owner_id' => $user->id,
            ]);

            $team->members()->attach($user->id);

            return $team->load('owner', 'members');
        });
    }

    public function show(int $teamId): Team {
        return Team::with(['owner', 'members', 'projects', 'invitations'])->findOrFail($teamId);
    }

    public function update(int $teamId, array $data, User $user): Team {
        $team = Team::findOrFail($teamId);

        $this->ensureOwner($team, $user);

        $team->update([
            'name' => $data['name'],
        ]);

        return $team->load('owner', 'members');
    }

    public function delete(int $teamId, User $user): void {
        DB::transaction(function () use ($teamId, $user) :void {
            $team = Team::with(['members', 'projects', 'invitations'])->findOrFail($teamId);
            
            $this->ensureOwner($team, $user);

            if ($team->members()->count() > 1) {
                throw new BusinessException('Não é possivel excluir times enquanto ele tiver membros.');
            }

            $team->invitations()->where('status', 'pending')->delete();
            $team->projects()->delete();
            $team->members()->detach();
            $team->delete();
        });
    }

    private function ensureOwner(Team $team, User $user): void {
        if ($team->owner_id !== $user->id) {
            throw new BusinessException('Apenas o dono do time pode realizar esta ação.', 403);
        }
    }
}