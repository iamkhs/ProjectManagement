<?php

namespace App\Services;

use App\Exceptions\TaskCreationException;
use App\Exceptions\TaskUpdateException;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskStatusChangedNotification;
use App\Repositories\TaskRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskService
{
    protected $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }


    public function findById(int $id)
    {
        try {
            return $this->taskRepository->findById($id);
        }catch (Exception $e){
            Log::error('Task not found', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
            throw new ModelNotFoundException('Task not found', 404);
        }
    }

    public function findByProject($projectId, $perPage)
    {
        try {
            return $this->taskRepository->getByProject($projectId, $perPage);
        }catch (Exception $e){
            Log::error('Task not found', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
            throw new ModelNotFoundException('Task not found', 404);
        }
    }

    public function create($data)
    {
        try {
            $authUser = auth()->user();
            $data['assigned_by'] = $authUser->id;
            return $this->taskRepository->create($data);
        }catch (Exception $e){
            Log::error('Task creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
            throw new TaskCreationException('Task creation failed', 500);
        }
    }

    public function update($id, $data)
    {
        try {
            $task = $this->taskRepository->findById($id);
            $oldStatus = $task->status;

            $updatedTask = $this->taskRepository->update($id, $data);

            if (isset($data['status']) && $data['status'] !== $oldStatus) {
                $assignedUser = User::find($task->assigned_to);
                if ($assignedUser) {
                    $assignedUser->notify(new TaskStatusChangedNotification($task, $oldStatus, $data['status']));
                }

                $teamLeader = $task->project->teamLeader ?? null;
                if ($teamLeader) {
                    $teamLeader->notify(new TaskStatusChangedNotification($task, $oldStatus, $data['status']));
                }
            }

            return $updatedTask;
        } catch (Exception $e) {
            Log::error('Task update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
            throw new TaskUpdateException('Task update failed', 500);
        }
    }

    public function delete($id)
    {
        $this->taskRepository->delete($id);
    }

    public function completeTask($id)
    {
        $task = Task::with('assignedTo', 'project.teamLeader')->findOrFail($id);

        if ($task->status === 'completed') {
            return null;
        }

        $oldStatus = $task->status;

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $assignedUser = $task->assignedTo;
        if ($assignedUser) {
            $assignedUser->notify(new TaskStatusChangedNotification($task, $oldStatus, 'completed'));
        }

        if ($task->project && $task->project->teamLeader) {
            $teamLeader = $task->project->teamLeader;
            $teamLeader->notify(new TaskStatusChangedNotification($task, $oldStatus, 'completed'));
        }

        return $task;
    }


    public function assignTask($id, $data)
    {
        $authUser = Auth::user();

        $assignedUser = User::findOrFail($data['user_id']);
        if ($assignedUser->role !== 'team_member') {
            return ['error' => 'Only Team Members can be assigned to tasks.', 'code' => 403];
        }

        $task = Task::with('project.teamLeader')->findOrFail($id);
        $projectId = $task->project_id;

        $isMember = ProjectMember::where('project_id', $projectId)
            ->where('user_id', $assignedUser->id)
            ->exists();

        if (!$isMember) {
            return ['error' => 'User is not part of this project.', 'code' => 403];
        }

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
        $task = Task::findOrFail($id);
        if (!$task->assigned_to) {
            return ['error' => 'No user is currently assigned to this task.', 'code' => 400];
        }

        $task->update([
            'assigned_to' => null,
        ]);

        return ['success' => true, 'task' => $task];
    }

}
