<?php

namespace Tests\Feature\Team;

use App\Models\Project;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_team(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'api')
            ->postJson('/api/teams', [
                'name' => 'Time de Produto',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('teams', [
            'name' => 'Time de Produto',
            'owner_id' => $user->id,
        ]);

        $team = Team::first();

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_cannot_create_team_without_name(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'api')
            ->postJson('/api/teams', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_owner_can_update_team(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamWithOwner($owner, 'Time Antigo');

        $response = $this
            ->actingAs($owner, 'api')
            ->putJson("/api/teams/{$team->id}", [
                'name' => 'Time Novo',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Time Novo',
        ]);
    }

    public function test_non_owner_cannot_update_team(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->putJson("/api/teams/{$team->id}", [
                'name' => 'Nome Novo',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_delete_team_when_there_are_no_other_members(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/teams/{$team->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('teams', [
            'id' => $team->id,
        ]);
    }

    public function test_owner_cannot_delete_team_when_there_are_other_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $team->members()->attach($member->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/teams/{$team->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
        ]);
    }

    public function test_non_owner_cannot_delete_team(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->deleteJson("/api/teams/{$team->id}");

        $response->assertStatus(403);
    }

    public function test_deleting_team_removes_pending_invitations(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        TeamInvitation::create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
            'email' => 'convite@teste.com',
            'status' => 'pending',
            'token' => 'token-pendente',
        ]);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/teams/{$team->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('team_invitations', [
            'team_id' => $team->id,
            'status' => 'pending',
        ]);
    }

    public function test_deleting_team_removes_projects_linked_to_team(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $project = Project::create([
            'name' => 'Projeto do Time',
            'description' => 'Descricao',
            'owner_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/teams/{$team->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_owner_can_send_team_invitation(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/teams/{$team->id}/invites", [
                'email' => 'novo@teste.com',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'novo@teste.com',
            'status' => 'pending',
        ]);
    }

    public function test_non_owner_cannot_send_team_invitation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->postJson("/api/teams/{$team->id}/invites", [
                'email' => 'novo@teste.com',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_cannot_invite_himself(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/teams/{$team->id}/invites", [
                'email' => $owner->email,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_invite_user_who_is_already_a_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $team->members()->attach($member->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/teams/{$team->id}/invites", [
                'email' => $member->email,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_send_duplicate_pending_invitation_to_same_email_for_same_team(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        TeamInvitation::create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
            'email' => 'duplicado@teste.com',
            'status' => 'pending',
            'token' => 'token-duplicado',
        ]);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/teams/{$team->id}/invites", [
                'email' => 'duplicado@teste.com',
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_accept_team_invitation(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'aceite@teste.com',
        ]);
        $team = $this->createTeamWithOwner($owner);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'pending',
            'token' => 'token-accept',
        ]);

        $response = $this
            ->actingAs($invitedUser, 'api')
            ->postJson("/api/teams/invites/{$invitation->token}/accept");

        $response->assertOk();

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $invitedUser->id,
        ]);

        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_cannot_accept_invitation_that_is_not_pending(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'aceite2@teste.com',
        ]);
        $team = $this->createTeamWithOwner($owner);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'accepted',
            'token' => 'token-ja-aceito',
        ]);

        $response = $this
            ->actingAs($invitedUser, 'api')
            ->postJson("/api/teams/invites/{$invitation->token}/accept");

        $response->assertStatus(422);
    }

    public function test_user_cannot_accept_invitation_from_another_email(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'um@teste.com',
        ]);
        $otherUser = User::factory()->create([
            'email' => 'outro@teste.com',
        ]);
        $team = $this->createTeamWithOwner($owner);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'pending',
            'token' => 'token-email-diferente',
        ]);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->postJson("/api/teams/invites/{$invitation->token}/accept");

        $response->assertStatus(403);
    }

    public function test_user_can_decline_team_invitation(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'recusa@teste.com',
        ]);
        $team = $this->createTeamWithOwner($owner);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'pending',
            'token' => 'token-decline',
        ]);

        $response = $this
            ->actingAs($invitedUser, 'api')
            ->postJson("/api/teams/invites/{$invitation->token}/decline");

        $response->assertOk();

        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
            'status' => 'declined',
        ]);
    }

    public function test_user_cannot_decline_invitation_that_is_not_pending(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'recusa2@teste.com',
        ]);
        $team = $this->createTeamWithOwner($owner);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'declined',
            'token' => 'token-ja-recusado',
        ]);

        $response = $this
            ->actingAs($invitedUser, 'api')
            ->postJson("/api/teams/invites/{$invitation->token}/decline");

        $response->assertStatus(422);
    }

    public function test_user_cannot_decline_invitation_from_another_email(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'destinatario@teste.com',
        ]);
        $otherUser = User::factory()->create([
            'email' => 'estranho@teste.com',
        ]);
        $team = $this->createTeamWithOwner($owner);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'pending',
            'token' => 'token-recusar-outro-email',
        ]);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->postJson("/api/teams/invites/{$invitation->token}/decline");

        $response->assertStatus(403);
    }

    public function test_owner_can_remove_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $team->members()->attach($member->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/teams/{$team->id}/members", [
                'user_id' => $member->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_owner_cannot_remove_himself(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/teams/{$team->id}/members", [
                'user_id' => $owner->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_non_owner_cannot_remove_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $otherUser = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $team->members()->attach($member->id);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->deleteJson("/api/teams/{$team->id}/members", [
                'user_id' => $member->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_remove_user_who_is_not_a_member(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/teams/{$team->id}/members", [
                'user_id' => $stranger->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_member_can_leave_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $team->members()->attach($member->id);

        $response = $this
            ->actingAs($member, 'api')
            ->postJson("/api/teams/{$team->id}/leave");

        $response->assertOk();

        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_owner_cannot_leave_team(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/teams/{$team->id}/leave");

        $response->assertStatus(422);
    }

    public function test_user_who_is_not_member_cannot_leave_team(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $response = $this
            ->actingAs($stranger, 'api')
            ->postJson("/api/teams/{$team->id}/leave");

        $response->assertStatus(422);
    }

    public function test_owner_can_create_team_project(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $team->members()->attach($member->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/teams/{$team->id}/projects", [
                'name' => 'Projeto do Time',
                'description' => 'Descricao do projeto',
            ]);

        $response->assertStatus(201);

        $project = Project::first();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Projeto do Time',
            'owner_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
        ]);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_non_owner_cannot_create_team_project(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamWithOwner($owner);

        $team->members()->attach($member->id);

        $response = $this
            ->actingAs($member, 'api')
            ->postJson("/api/teams/{$team->id}/projects", [
                'name' => 'Projeto Invalido',
                'description' => 'Descricao do projeto',
            ]);

        $response->assertStatus(403);
    }

    private function createTeamWithOwner(User $owner, string $name = 'Time Base'): Team
    {
        $team = Team::create([
            'name' => $name,
            'owner_id' => $owner->id,
        ]);

        $team->members()->attach($owner->id);

        return $team;
    }
}
