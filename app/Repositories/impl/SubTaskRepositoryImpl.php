<?php

namespace App\Repositories\impl;

use App\Models\SubTask;
use App\Repositories\SubTaskRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubTaskRepositoryImpl implements SubTaskRepository
{

    public function findById(int $id)
    {
        return SubTask::with('assignedTo', 'assignedBy')
            ->findOrFail($id);
    }

    public function getByTask(int $taskId, int $perPage = 10): LengthAwarePaginator
    {
        return SubTask::where('task_id', $taskId)
            ->with('assignedTo', 'assignedBy')
            ->paginate($perPage);
    }

    public function create($data)
    {
        return SubTask::create($data);
    }

    public function update($id, $data)
    {
        $task = SubTask::findOrFail($id);
        $task->update($data);
        return $task;
    }

    public function delete($id)
    {
        $task = SubTask::findOrFail($id);
        $task->delete();
    }
}
