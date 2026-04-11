<?php

namespace App\Http\Services\Project;

use App\Exceptions\BusinessException;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectInviteService {

    public function send(int $projectId, array $data, User $user):ProjectInvitation {
        $project = Project::findOrFail($projectId);

        $this->ensureOwner($project, $user);

        if ($user->email === $data['email']) {
            throw new BusinessException('O dono do projeto não pode convidar a si mesmo.');
        }

        $invitedUser= User::where('email', $data['email'])->first();

        if ($invitedUser && $project->members()->where('users.id', $invitedUser->id)->exists()) {
            throw new BusinessException('Este usuário ja faz parte do projeto.');
        }

        $hasPendingInvite = $project->invitations()->where('email', $data['email'])->where('status', 'pending')->exists();

        if ($hasPendingInvite) {
            throw new BusinessException('Já existe um convite pendente para este email neste projeto.');
        }

        return $project->invitations()->create([
            'invited_by' => $user->id,
            'email' => $data['email'],
            'status' => 'pending',
            'token' => Str::uuid()->toString(),
        ]);
    }

    public function accept(string $token, User $user): ProjectInvitation {
        return DB::transaction(function () use ($token, $user): ProjectInvitation {
            $invitation = ProjectInvitation::with('project')->where('token', $token)->firstOrFail();

            if ($invitation->status !== 'pending') {
                throw New BusinessException('Este convite não está mais pendente.');
            }

            if ($invitation->email !== $user->email) {
                throw New BusinessException('Este convite não pertence ao usuário autenticado.', 403);
            }

            $alreadyMember = $invitation->project->members()->where('users.id', $user->id)->exists();

            if ($alreadyMember) {
                throw New BusinessException('O usuário ja faz parte do projeto.');
            }

            $invitation->project->members()->attach($user->id);
            $invitation->update([
                'status' => 'accepted',
            ]);

            return $invitation->fresh();        
        });
    }

    public function decline(string $token, User $user): ProjectInvitation {
        $invitation = ProjectInvitation::where('token', $token)->firstOrFail();

        if ($invitation->status !== 'pending') {
            throw New BusinessException('Este convite não está mais pendente.');
        }

        if ($invitation->email !== $user->email) {
            throw New BusinessException('Este convite não pertence ao usuário autenticado.', 403);
        }

        $invitation->update([
            'status' => 'declined',
        ]);

        return $invitation;
    }

    private function ensureOwner(Project $project, User $user): void {
        if ((int) $project->owner_id !== (int) $user->id) {
            throw new BusinessException('Apenas o dono do projeto pode realizar esta ação.', 403);
        }
    }
}