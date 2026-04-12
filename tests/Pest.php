<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

function createProjectWithOwner(User $owner, string $name = 'Projeto Base'): Project {
    $project = Project::create([
        'name' => $name,
        'owner_id' => $owner->id,
    ]);

    $project->members()->attach($owner->id);

    return $project;
}

function createTask(
    Project $project,
    User $creator,
    string $title = 'Task Base',
    ?string $dueDate = null,
): Task {
    return Task::create([
        'title' => $title,
        'description' => 'Descricao da task',
        'project_id' => $project->id,
        'created_by' => $creator->id,
        'status' => 'pending',
        'due_date' => $dueDate,
    ]);
}
