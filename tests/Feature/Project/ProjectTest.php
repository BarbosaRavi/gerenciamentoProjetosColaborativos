<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_project(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'api')
            ->postJson('/api/projects', [
                'name' => 'Projeto Alpha',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('projects', [
            'name' => 'Projeto Alpha',
            'owner_id' => $user->id,
        ]);

        $project = Project::first();

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_cannot_create_project_without_name(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'api')
            ->postJson('/api/projects', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_owner_can_update_project(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner, 'Projeto Antigo');

        $response = $this
            ->actingAs($owner, 'api')
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Projeto Novo',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Projeto Novo',
        ]);
    }

    public function test_non_owner_cannot_update_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Nome Novo',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_delete_project_when_there_are_no_other_members(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_owner_cannot_delete_project_when_there_are_other_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $project->members()->attach($member->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_non_owner_cannot_delete_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
    }

    public function test_deleting_project_removes_pending_invitations(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => 'convite@teste.com',
            'status' => 'pending',
            'token' => 'token-pendente',
        ]);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('project_invitations', [
            'project_id' => $project->id,
            'status' => 'pending',
        ]);
    }

    public function test_owner_can_send_project_invitation(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/projects/{$project->id}/invites", [
                'email' => 'novo@teste.com',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('project_invitations', [
            'project_id' => $project->id,
            'email' => 'novo@teste.com',
            'status' => 'pending',
        ]);
    }

    public function test_non_owner_cannot_send_project_invitation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->postJson("/api/projects/{$project->id}/invites", [
                'email' => 'novo@teste.com',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_cannot_invite_himself_to_project(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/projects/{$project->id}/invites", [
                'email' => $owner->email,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_invite_user_who_is_already_a_project_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $project->members()->attach($member->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/projects/{$project->id}/invites", [
                'email' => $member->email,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_send_duplicate_pending_invitation_to_same_email_for_same_project(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => 'duplicado@teste.com',
            'status' => 'pending',
            'token' => 'token-duplicado',
        ]);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/projects/{$project->id}/invites", [
                'email' => 'duplicado@teste.com',
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_accept_project_invitation(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'aceite@teste.com',
        ]);
        $project = $this->createProjectWithOwner($owner);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'pending',
            'token' => 'token-accept',
        ]);

        $response = $this
            ->actingAs($invitedUser, 'api')
            ->postJson("/api/projects/invites/{$invitation->token}/accept");

        $response->assertOk();

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $invitedUser->id,
        ]);

        $this->assertDatabaseHas('project_invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_cannot_accept_project_invitation_that_is_not_pending(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'aceite2@teste.com',
        ]);
        $project = $this->createProjectWithOwner($owner);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'accepted',
            'token' => 'token-ja-aceito',
        ]);

        $response = $this
            ->actingAs($invitedUser, 'api')
            ->postJson("/api/projects/invites/{$invitation->token}/accept");

        $response->assertStatus(422);
    }

    public function test_user_cannot_accept_project_invitation_from_another_email(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'um@teste.com',
        ]);
        $otherUser = User::factory()->create([
            'email' => 'outro@teste.com',
        ]);
        $project = $this->createProjectWithOwner($owner);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'pending',
            'token' => 'token-email-diferente',
        ]);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->postJson("/api/projects/invites/{$invitation->token}/accept");

        $response->assertStatus(403);
    }

    public function test_user_can_decline_project_invitation(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'recusa@teste.com',
        ]);
        $project = $this->createProjectWithOwner($owner);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'pending',
            'token' => 'token-decline',
        ]);

        $response = $this
            ->actingAs($invitedUser, 'api')
            ->postJson("/api/projects/invites/{$invitation->token}/decline");

        $response->assertOk();

        $this->assertDatabaseHas('project_invitations', [
            'id' => $invitation->id,
            'status' => 'declined',
        ]);
    }

    public function test_user_cannot_decline_project_invitation_that_is_not_pending(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'recusa2@teste.com',
        ]);
        $project = $this->createProjectWithOwner($owner);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'declined',
            'token' => 'token-ja-recusado',
        ]);

        $response = $this
            ->actingAs($invitedUser, 'api')
            ->postJson("/api/projects/invites/{$invitation->token}/decline");

        $response->assertStatus(422);
    }

    public function test_user_cannot_decline_project_invitation_from_another_email(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'destinatario@teste.com',
        ]);
        $otherUser = User::factory()->create([
            'email' => 'estranho@teste.com',
        ]);
        $project = $this->createProjectWithOwner($owner);

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => $invitedUser->email,
            'status' => 'pending',
            'token' => 'token-recusar-outro-email',
        ]);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->postJson("/api/projects/invites/{$invitation->token}/decline");

        $response->assertStatus(403);
    }

    public function test_owner_can_remove_project_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $project->members()->attach($member->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/projects/{$project->id}/members", [
                'user_id' => $member->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_owner_cannot_remove_himself_from_project(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/projects/{$project->id}/members", [
                'user_id' => $owner->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_non_owner_cannot_remove_project_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $project->members()->attach($member->id);

        $response = $this
            ->actingAs($otherUser, 'api')
            ->deleteJson("/api/projects/{$project->id}/members", [
                'user_id' => $member->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_remove_user_who_is_not_a_project_member(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/projects/{$project->id}/members", [
                'user_id' => $stranger->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_member_can_leave_project(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $project->members()->attach($member->id);

        $response = $this
            ->actingAs($member, 'api')
            ->postJson("/api/projects/{$project->id}/leave");

        $response->assertOk();

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_owner_cannot_leave_project(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/projects/{$project->id}/leave");

        $response->assertStatus(422);
    }

    public function test_user_who_is_not_member_cannot_leave_project(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($stranger, 'api')
            ->postJson("/api/projects/{$project->id}/leave");

        $response->assertStatus(422);
    }

    private function createProjectWithOwner(User $owner, string $name = 'Projeto Base'): Project
    {
        $project = Project::create([
            'name' => $name,
            'owner_id' => $owner->id,
        ]);

        $project->members()->attach($owner->id);

        return $project;
    }
}
