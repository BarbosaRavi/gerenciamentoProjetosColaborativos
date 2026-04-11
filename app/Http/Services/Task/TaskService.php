<?php

namespace App\Http\Services\Task;

use App\Exceptions\BusinessException;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskService
{
    private const ALLOWED_STATUSES = [
        'pending',
        'in_progress',
        'on_hold',
        'completed',
    ];

    public function create(int $projectId, array $data, User $user): Task {
        return DB::transaction(function () use ($projectId, $data, $user): Task {
            $project = Project::with('members')->findOrFail($projectId);

            $this->ensureProjectMember($project, $user);

            $task = Task::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'project_id' => $project->id,
                'created_by' => $user->id,
                'status' => $data['status'] ?? 'pending',
            ]);

            return $task->load(['project', 'creator', 'assignees', 'tags']);
        });
    }

    public function show(int $taskId, User $user): Task {
        $task = Task::with(['project.members', 'creator', 'assignees', 'tags', 'comments'])->findOrFail($taskId);

        $this->ensureProjectMember($task->project, $user);

        return $task;
    }

    public function update(int $taskId, array $data, User $user): Task {
        $task = Task::with('project.members')->findOrFail($taskId);

        $this->ensureProjectMember($task->project, $user);

        if (isset($data['status'])) {
            $this->ensureValidStatus($data['status']);
        }

        $task->update([
            'title' => $data['title'] ?? $task->title,
            'description' => $data['description'] ?? $task->description,
            'status' => $data['status'] ?? $task->status,
        ]);

        return $task->load(['project', 'creator', 'assignees', 'tags']);
    }

    public function updateStatus(int $taskId, string $status, User $user): Task {
        $task = Task::with('project.members')->findOrFail($taskId);

        $this->ensureProjectMember($task->project, $user);
        $this->ensureValidStatus($status);

        $task->update([
            'status' => $status,
        ]);

        return $task->load(['project', 'creator', 'assignees', 'tags']);
    }

    public function delete(int $taskId, User $user): void {
        DB::transaction(function () use ($taskId, $user): void {
            $task = Task::with(['project.members', 'assignees', 'tags'])->findOrFail($taskId);

            $this->ensureProjectMember($task->project, $user);

            $task->tags()->detach();
            $task->assignees()->detach();
            $task->delete();
        });
    }

    public function assignMember(int $taskId, int $userId, User $user): Task {
        return DB::transaction(function () use ($taskId, $userId, $user): Task {
            $task = Task::with('project.members')->findOrFail($taskId);

            $this->ensureProjectMember($task->project, $user);

            $targetIsProjectMember = $task->project->members()->where('users.id', $userId)->exists();

            if (! $targetIsProjectMember) {
                throw new BusinessException('O usuário informado não faz parte do projeto.');
            }

            $alreadyAssigned = $task->assignees()->where('users.id', $userId)->exists();

            if ($alreadyAssigned) {
                throw new BusinessException('O usuário já está atribuído a esta atividade.');
            }

            $task->assignees()->attach($userId);

            return $task->fresh()->load(['project', 'creator', 'assignees', 'tags']);
        });
    }

    public function removeMember(int $taskId, int $userId, User $user): Task {
        return DB::transaction(function () use ($taskId, $userId, $user): Task {
            $task = Task::with('project.members')->findOrFail($taskId);

            $this->ensureProjectMember($task->project, $user);

            $assigned = $task->assignees()->where('users.id', $userId)->exists();

            if (! $assigned) {
                throw new BusinessException('O usuário informado não está atribuído a esta atividade.');
            }

            $task->assignees()->detach($userId);

            return $task->fresh()->load(['project', 'creator', 'assignees', 'tags']);
        });
    }

    public function attachTag(int $taskId, int $tagId, User $user): Task {
        return DB::transaction(function () use ($taskId, $tagId, $user): Task {
            $task = Task::with('project.members')->findOrFail($taskId);
            $tag = Tag::findOrFail($tagId);

            $this->ensureProjectMember($task->project, $user);

            if ((int) $tag->project_id !== (int) $task->project_id) {
                throw new BusinessException('A tag informada não pertence ao mesmo projeto da atividade.');
            }

            $alreadyAttached = $task->tags()->where('tags.id', $tagId)->exists();

            if ($alreadyAttached) {
                throw new BusinessException('A tag já está vinculada a esta atividade.');
            }

            $task->tags()->attach($tagId);

            return $task->fresh()->load(['project', 'creator', 'assignees', 'tags']);
        });
    }

    public function detachTag(int $taskId, int $tagId, User $user): Task {
        return DB::transaction(function () use ($taskId, $tagId, $user): Task {
            $task = Task::with('project.members')->findOrFail($taskId);

            $this->ensureProjectMember($task->project, $user);

            $attached = $task->tags()->where('tags.id', $tagId)->exists();

            if (! $attached) {
                throw new BusinessException('A tag informada não está vinculada a esta atividade.');
            }

            $task->tags()->detach($tagId);

            return $task->fresh()->load(['project', 'creator', 'assignees', 'tags']);
        });
    }

    private function ensureProjectMember(Project $project, User $user): void {
        $isMember = $project->members()->where('users.id', $user->id)->exists();

        if (! $isMember) {
            throw new BusinessException('Apenas membros do projeto podem realizar esta ação.', 403);
        }
    }

    private function ensureValidStatus(string $status): void {
        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new BusinessException('Status da atividade inválido.');
        }
    }
}
