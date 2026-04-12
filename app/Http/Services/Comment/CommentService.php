<?php

namespace App\Http\Services\Comment;

use App\Exceptions\BusinessException;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CommentService {

    public function create(int $taskId, array $data, User $user): Comment {
        return DB::transaction(function () use ($taskId, $data, $user): Comment {
            $task = Task::with('project.members')->findOrFail($taskId);

            $this->ensureProjectMember($task, $user);

            if (isset($data['parent_id']) && ! is_null($data['parent_id'])) {
                $parentComment = Comment::findOrFail($data['parent_id']);

                if ((int) $parentComment->task_id !== (int) $task->id) {
                    throw new BusinessException('O comentário pai não pertence à mesma atividade.');
                }
            }

            $comment = Comment::create([
                'content' => $data['content'],
                'user_id' => $user->id,
                'task_id' => $task->id,
                'parent_id' => $data['parent_id'] ?? null,
            ]);

            return $comment->load(['author', 'replies.author']);
        });
    }

    public function listByTask(int $taskId, User $user): Collection {
        $task = Task::with('project.members')->findOrFail($taskId);

        $this->ensureProjectMember($task, $user);

        return Comment::with(['author', 'replies.author'])->where('task_id', $task->id)->whereNull('parent_id')->orderBy('created_at')->get();
    }

    public function update(int $commentId, array $data, User $user): Comment {
        $comment = Comment::with('task.project.members')->findOrFail($commentId);

        $this->ensureProjectMember($comment->task, $user);
        $this->ensureAuthor($comment, $user);

        $comment->update([
            'content' => $data['content'],
        ]);

        return $comment->load(['author', 'replies.author']);
    }

    public function delete(int $commentId, User $user): void {
        DB::transaction(function () use ($commentId, $user): void {
            $comment = Comment::with(['task.project.members', 'replies'])->findOrFail($commentId);

            $this->ensureProjectMember($comment->task, $user);
            $this->ensureAuthor($comment, $user);

            $comment->replies()->delete();
            $comment->delete();
        });
    }

    private function ensureProjectMember(Task $task, User $user): void {
        $isMember = $task->project->members()->where('users.id', $user->id)->exists();

        if (! $isMember) {
            throw new BusinessException('Apenas membros do projeto podem realizar esta ação.', 403);
        }
    }

    private function ensureAuthor(Comment $comment, User $user): void {
        if ((int) $comment->user_id !== (int) $user->id) {
            throw new BusinessException('Apenas o autor do comentário pode realizar esta ação.', 403);
        }
    }
}
