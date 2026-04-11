<?php

namespace Tests\Feature\Task;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_member_can_create_task(): void
    {
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($member);

        $response = $this
            ->actingAs($member, 'api')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Implementar login',
                'description' => 'Criar fluxo de autenticação',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Implementar login',
            'description' => 'Criar fluxo de autenticação',
            'project_id' => $project->id,
            'created_by' => $member->id,
            'status' => 'pending',
        ]);
    }

    public function test_non_project_member_cannot_create_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $response = $this
            ->actingAs($stranger, 'api')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Implementar login',
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_create_task_without_title(): void
    {
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($member);

        $response = $this
            ->actingAs($member, 'api')
            ->postJson("/api/projects/{$project->id}/tasks", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_project_member_can_view_task(): void
    {
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($member);
        $task = $this->createTask($project, $member);

        $response = $this
            ->actingAs($member, 'api')
            ->getJson("/api/tasks/{$task->id}");

        $response->assertOk();
    }

    public function test_non_project_member_cannot_view_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);
        $task = $this->createTask($project, $owner);

        $response = $this
            ->actingAs($stranger, 'api')
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_project_member_can_update_task(): void
    {
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($member);
        $task = $this->createTask($project, $member, 'Titulo Antigo');

        $response = $this
            ->actingAs($member, 'api')
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Titulo Novo',
                'description' => 'Descricao nova',
                'status' => 'in_progress',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Titulo Novo',
            'description' => 'Descricao nova',
            'status' => 'in_progress',
        ]);
    }

    public function test_non_project_member_cannot_update_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);
        $task = $this->createTask($project, $owner);

        $response = $this
            ->actingAs($stranger, 'api')
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Novo titulo',
            ]);

        $response->assertStatus(403);
    }

    public function test_project_member_can_update_task_status(): void
    {
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($member);
        $task = $this->createTask($project, $member);

        $response = $this
            ->actingAs($member, 'api')
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function test_cannot_update_task_with_invalid_status(): void
    {
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($member);
        $task = $this->createTask($project, $member);

        $response = $this
            ->actingAs($member, 'api')
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(422);
    }

    public function test_project_member_can_delete_task(): void
    {
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($member);
        $task = $this->createTask($project, $member);

        $response = $this
            ->actingAs($member, 'api')
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_non_project_member_cannot_delete_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);
        $task = $this->createTask($project, $owner);

        $response = $this
            ->actingAs($stranger, 'api')
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_project_member_can_assign_user_to_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $project->members()->attach($member->id);
        $task = $this->createTask($project, $owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/tasks/{$task->id}/members", [
                'user_id' => $member->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_cannot_assign_user_who_is_not_in_project(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);
        $task = $this->createTask($project, $owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/tasks/{$task->id}/members", [
                'user_id' => $stranger->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_assign_duplicate_user_to_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $project->members()->attach($member->id);
        $task = $this->createTask($project, $owner);
        $task->assignees()->attach($member->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/tasks/{$task->id}/members", [
                'user_id' => $member->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_project_member_can_remove_assigned_user_from_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $project->members()->attach($member->id);
        $task = $this->createTask($project, $owner);
        $task->assignees()->attach($member->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/tasks/{$task->id}/members", [
                'user_id' => $member->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseMissing('task_user', [
            'task_id' => $task->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_cannot_remove_user_who_is_not_assigned_to_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);

        $project->members()->attach($member->id);
        $task = $this->createTask($project, $owner);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/tasks/{$task->id}/members", [
                'user_id' => $member->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_project_member_can_attach_tag_to_task(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);
        $task = $this->createTask($project, $owner);
        $tag = Tag::create([
            'project_id' => $project->id,
            'name' => 'Backend',
            'slug' => 'backend',
        ]);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/tasks/{$task->id}/tags", [
                'tag_id' => $tag->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('tag_task', [
            'task_id' => $task->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_cannot_attach_tag_from_another_project(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();

        $project = $this->createProjectWithOwner($owner);
        $otherProject = $this->createProjectWithOwner($otherOwner);

        $task = $this->createTask($project, $owner);
        $foreignTag = Tag::create([
            'project_id' => $otherProject->id,
            'name' => 'Frontend',
            'slug' => 'frontend',
        ]);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/tasks/{$task->id}/tags", [
                'tag_id' => $foreignTag->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_attach_duplicate_tag_to_task(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);
        $task = $this->createTask($project, $owner);
        $tag = Tag::create([
            'project_id' => $project->id,
            'name' => 'Urgente',
            'slug' => 'urgente',
        ]);

        $task->tags()->attach($tag->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/tasks/{$task->id}/tags", [
                'tag_id' => $tag->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_project_member_can_detach_tag_from_task(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);
        $task = $this->createTask($project, $owner);
        $tag = Tag::create([
            'project_id' => $project->id,
            'name' => 'API',
            'slug' => 'api',
        ]);

        $task->tags()->attach($tag->id);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/tasks/{$task->id}/tags", [
                'tag_id' => $tag->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseMissing('tag_task', [
            'task_id' => $task->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_cannot_detach_tag_that_is_not_attached_to_task(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectWithOwner($owner);
        $task = $this->createTask($project, $owner);
        $tag = Tag::create([
            'project_id' => $project->id,
            'name' => 'Infra',
            'slug' => 'infra',
        ]);

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/tasks/{$task->id}/tags", [
                'tag_id' => $tag->id,
            ]);

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

    private function createTask(Project $project, User $creator, string $title = 'Task Base'): Task
    {
        return Task::create([
            'title' => $title,
            'description' => 'Descricao da task',
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'status' => 'pending',
        ]);
    }
}
