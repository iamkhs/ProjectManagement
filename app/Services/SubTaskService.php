<?php

namespace App\Services;

use App\Exceptions\SubTaskCreationException;
use App\Exceptions\SubTaskUpdateException;
use App\Exceptions\TaskCreationException;
use App\Models\ProjectMember;
use App\Models\SubTask;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskStatusChangedNotification;
use App\Repositories\SubTaskRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubTaskService
{
    protected $subTaskRepository;

    public function __construct(SubTaskRepository $subTaskRepository)
    {
        $this->subTaskRepository = $subTaskRepository;
    }

    public function findById(int $id)
    {
        try {
            return $this->subTaskRepository->findById($id);
        }catch (Exception $e){
            Log::error('Sub Task not found', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
            throw new ModelNotFoundException('Sub Task not found', 404);
        }
    }

    public function findByTask($task, $perPage)
    {
        try {
            return $this->subTaskRepository->getByTask($task, $perPage);
        }catch (Exception $e){
            Log::error('Sub Task not found', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
            throw new ModelNotFoundException('Sub Task not found', 404);
        }
    }

    public function create($data)
    {
        try {
            $authUser = auth()->user();
            $data['assigned_by'] = $authUser->id;
            return $this->subTaskRepository->create($data);
        }catch (Exception $e){
            Log::error('Task creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
            throw new SubTaskCreationException('SubTask creation failed', 500);
        }
    }


    public function update($id, $data)
    {
        try {
            $subTask = $this->subTaskRepository->findById($id);
            $oldStatus = $subTask->status;
            $updatedTask =  $this->subTaskRepository->update($id, $data);

            if (isset($data['status']) && $data['status'] !== $oldStatus) {
                $assignedUser = User::find($subTask->assigned_to);
                if ($assignedUser) {
                    $assignedUser->notify(new TaskStatusChangedNotification($subTask, $oldStatus, $data['status']));
                }
                $task = Task::with('project')->findOrFail($subTask->id);

                $teamLeader = $task->project->teamLeader ?? null;
                if ($teamLeader) {
                    $teamLeader->notify(new TaskStatusChangedNotification($task, $oldStatus, $data['status']));
                }
            }
            return $updatedTask;
        } catch (Exception $e) {
            Log::error('SuTask update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
            throw new SubTaskUpdateException('SubTask update failed', 500);
        }
    }

    public function delete($id)
    {
        $this->subTaskRepository->delete($id);
    }

    public function completeTask($id)
    {
        $subTask = SubTask::with('task.project', 'assignedTo')->findOrFail($id);
        if ($subTask->status === 'completed') {
            return null;
        }
        $subTask->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $oldStatus = $subTask->status;

        $subTask->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $assignedUser = $subTask->assignedTo;
        if ($assignedUser) {
            $assignedUser->notify(new TaskStatusChangedNotification($subTask, $oldStatus, 'completed'));
        }

        if ($subTask->task->project && $subTask->task->project->teamLeader) {
            $teamLeader = $subTask->project->teamLeader;
            $teamLeader->notify(new TaskStatusChangedNotification($subTask, $oldStatus, 'completed'));
        }

        return $subTask;
    }

    public function assignTask($id, $data)
    {
        $authUser = Auth::user();

        $assignedUser = User::findOrFail($data['user_id']);
        if ($assignedUser->role !== 'team_member') {
            return ['error' => 'Only Team Members can be assigned to tasks.', 'code' => 403];
        }

        $task = SubTask::findOrFail($id);

        $task->update([
            'assigned_to' => $assignedUser->id,
            'assigned_by' => $authUser->id,
        ]);

        $assignedUser->notify(new TaskAssignedNotification($task));
        if ($task->project && $task->project->teamLeader) {
            $teamLeader = $task->project->teamLeader;
            $teamLeader->notify(new TaskAssignedNotification($task));
        }

        return ['success' => true, 'task' => $task];
    }

    public function unassignTask($id)
    {
        $task = SubTask::findOrFail($id);
        if (!$task->assigned_to) {
            return ['error' => 'No user is currently assigned to this task.', 'code' => 400];
        }

        $task->update([
            'assigned_to' => null,
        ]);

        return ['success' => true, 'task' => $task];
    }


}
