<?php

namespace App\Http\Services\Team;

use App\Exceptions\BusinessException;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamInviteService {

    public function send(int $teamId, array $data, User $user):TeamInvitation {
        $team = Team::findOrFail($teamId);

        $this->ensureOwner($team, $user);

        if ($user->email === $data['email']) {
            throw new BusinessException('O dono do time não pode convidar a si mesmo.');
        }

        $invitedUser= User::where('email', $data['email'])->first();

        if ($invitedUser && $team->members()->where('users.id', $invitedUser->id)->exists()) {
            throw new BusinessException('Este usuário ja faz parte do time.');
        }

        $hasPendingInvite = $team->invitations()->where('email', $data['email'])->where('status', 'pending')->exists();

        if ($hasPendingInvite) {
            throw new BusinessException('Já existe um convite pendente para este email neste time.');
        }

        return $team->invitations()->create([
            'invited_by' => $user->id,
            'email' => $data['email'],
            'status' => 'pending',
            'token' => Str::uuid()->toString(),
        ]);
    }

    public function accept(string $token, User $user): TeamInvitation {
        return DB::transaction(function () use ($token, $user): TeamInvitation {
            $invitation = TeamInvitation::with('team')->where('token', $token)->firstOrFail();

            if ($invitation->status !== 'pending') {
                throw New BusinessException('Este convite não está mais pendente.');
            }

            if ($invitation->email !== $user->email) {
                throw New BusinessException('Este convite não pertence ao usuário autenticado.', 403);
            }

            $alreadyMember = $invitation->team->members()->where('users.id', $user->id)->exists();

            if ($alreadyMember) {
                throw New BusinessException('O usuário ja faz parte do time.');
            }

            $invitation->team->members()->attach($user->id);
            $invitation->update([
                'status' => 'accepted',
            ]);

            return $invitation->fresh();        
        });
    }

    public function decline(string $token, User $user): TeamInvitation {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

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

    private function ensureOwner(Team $team, User $user): void {
        if ((int) $team->owner_id !== (int) $user->id) {
            throw new BusinessException('Apenas o dono pode realizar essa ação.', 403);
        }
    }
}