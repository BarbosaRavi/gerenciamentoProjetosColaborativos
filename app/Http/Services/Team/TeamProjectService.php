<?php

namespace App\Http\Services\Team;

use App\Exceptions\BusinessException;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamProjectService
{
    public function create(int $teamId, array $data, User $user): Project
    {
        return DB::transaction(function () use ($teamId, $data, $user): Project {
            $team = Team::with('members')->findOrFail($teamId);

            $this->ensureOwner($team, $user);

            $project = Project::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'owner_id' => $user->id,
                'team_id' => $team->id,
            ]);

            $memberIds = $team->members->pluck('id')->toArray();

            $project->members()->attach($memberIds);

            return $project->load('owner', 'team', 'members');
        });
    }

    private function ensureOwner(Team $team, User $user): void {
        if ((int) $team->owner_id !== (int) $user->id) {
            throw new BusinessException('Apenas o dono do time pode realizar esta ação.', 403);
        }
    }
}
