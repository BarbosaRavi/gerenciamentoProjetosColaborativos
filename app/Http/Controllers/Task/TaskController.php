<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\AssignTaskMemberRequest;
use App\Http\Requests\Task\AttachTaskTagRequest;
use App\Http\Requests\Task\DetachTaskTagRequest;
use App\Http\Requests\Task\RemoveTaskMemberRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Http\Services\Task\TaskService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function store(StoreTaskRequest $request, int $projectId): JsonResponse
    {
        $task = $this->taskService->create(
            $projectId,
            $request->validated(),
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Atividade criada com sucesso.',
            new TaskResource($task),
            201,
        );
    }

    public function show(int $taskId): JsonResponse
    {
        $task = $this->taskService->show($taskId, auth('api')->user());

        return ApiResponse::success(
            'Atividade encontrada com sucesso.',
            new TaskResource($task),
        );
    }

    public function update(UpdateTaskRequest $request, int $taskId): JsonResponse
    {
        $task = $this->taskService->update(
            $taskId,
            $request->validated(),
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Atividade atualizada com sucesso.',
            new TaskResource($task),
        );
    }

    public function updateStatus(UpdateTaskStatusRequest $request, int $taskId): JsonResponse
    {
        $task = $this->taskService->updateStatus(
            $taskId,
            $request->validated()['status'],
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Status da atividade atualizado com sucesso.',
            new TaskResource($task),
        );
    }

    public function destroy(int $taskId): JsonResponse
    {
        $this->taskService->delete($taskId, auth('api')->user());

        return ApiResponse::success('Atividade excluída com sucesso.');
    }

    public function assignMember(AssignTaskMemberRequest $request, int $taskId): JsonResponse
    {
        $task = $this->taskService->assignMember(
            $taskId,
            $request->validated()['user_id'],
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Responsável atribuído com sucesso.',
            new TaskResource($task),
        );
    }

    public function removeMember(RemoveTaskMemberRequest $request, int $taskId): JsonResponse
    {
        $task = $this->taskService->removeMember(
            $taskId,
            $request->validated()['user_id'],
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Responsável removido com sucesso.',
            new TaskResource($task),
        );
    }

    public function attachTag(AttachTaskTagRequest $request, int $taskId): JsonResponse
    {
        $task = $this->taskService->attachTag(
            $taskId,
            $request->validated()['tag_id'],
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Tag vinculada com sucesso.',
            new TaskResource($task),
        );
    }

    public function detachTag(DetachTaskTagRequest $request, int $taskId): JsonResponse
    {
        $task = $this->taskService->detachTag(
            $taskId,
            $request->validated()['tag_id'],
            auth('api')->user(),
        );

        return ApiResponse::success(
            'Tag removida com sucesso.',
            new TaskResource($task),
        );
    }
}
