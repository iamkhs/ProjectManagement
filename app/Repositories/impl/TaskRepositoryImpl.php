<?php

namespace App\Repositories\impl;

use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskRepositoryImpl implements TaskRepository
{

    public function findById(int $id)
    {
        return Task::with('project', 'subtasks', 'assignedTo', 'assignedBy')
            ->findOrFail($id);
    }

    public function getByProject(int $projectId, int $perPage = 10): LengthAwarePaginator
    {
        return Task::where('project_id', $projectId)
            ->with('subtasks', 'assignedTo', 'assignedBy')
            ->paginate($perPage);
    }

    public function create($data): Task
    {
        return Task::create($data);
    }

    public function update($id, $data): Task
    {
        $task = Task::findOrFail($id);
        $task->update($data);
        return $task;
    }

    public function delete($id): void
    {
        $task = Task::findOrFail($id);
        $task->delete();
    }
}
