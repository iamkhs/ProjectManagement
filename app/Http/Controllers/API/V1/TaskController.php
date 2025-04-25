<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    protected TaskService  $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function show($id)
    {
        $task = $this->taskService->findById($id);
        $this->authorize('view', $task);
        return $task;
    }

    public function findByProject($projectId, Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $perPage = (int) $request->query('perPage', 10);
        $data = $this->taskService->findByProject($projectId, $perPage);
        return response()->json(['message'=> 'Tasks by Project',
            'tasks'=> $data,
            'status'=> 200]);
    }

    public function store(TaskStoreRequest $request)
    {
        $data = $request->validated();

        $project = Project::findOrFail($data['project_id']);
        $this->authorize('create', $project);

        $task = $this->taskService->create($data);

        return response()->json([
            'message' => 'Task successfully created',
            'task' => $task,
            'status' => 201,
        ], 201);
    }

    public function update($id, TaskUpdateRequest $request)
    {
        $task = Task::with('project')->findOrFail($id);
        $this->authorize('update', $task);

        $data = $request->validated();

        $user = auth()->user();

        // If the user is a team_member, they can only update `status`
        if ($user->role === 'team_member') {
            $allowedKeys = ['status'];

            // Check if user is updating ONLY the status
            $attemptedKeys = array_keys($data);
            $unauthorizedFields = array_diff($attemptedKeys, $allowedKeys);

            if (!empty($unauthorizedFields)) {
                return response()->json([
                    'message' => 'You are only allowed to update the task status.',
                    'status' => 403
                ], 403);
            }
        }

        $updatedTask = $this->taskService->update($id, $data);

        return response()->json([
            'message' => 'Task successfully updated',
            'task' => $updatedTask,
            'status' => 200
        ]);
    }

    public function delete($id)
    {
        $task = Task::with('project')->findOrFail($id);
        $this->authorize('delete', $task);

        $this->taskService->delete($id);

        return response()->json([
            'message' => 'Task deleted successfully',
            'status' => 200
        ]);
    }


    public function markAsComplete($id)
    {
        $task = Task::with('project')->findOrFail($id);
        $this->authorize('markAsComplete', $task);

        $completedTask = $this->taskService->completeTask($id);

        if ($completedTask) {
            return response()->json([
                'message' => 'Task marked as completed',
                'task' => $completedTask,
                'status' => 200
            ]);
        }

        return response()->json([
            'message' => 'Task already completed.',
            'status' => 400
        ]);
    }


    public function assign($id, Request $request)
    {
        $task = Task::with('project')->findOrFail($id);
        $this->authorize('assign', $task);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $result = $this->taskService->assignTask($id, $data);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json([
            'message' => 'Task successfully assigned to user',
            'status' => 200
        ]);
    }


    public function unassign($id)
    {
        $task = Task::with('project')->findOrFail($id);
        $this->authorize('unassign', $task);

        $result = $this->taskService->unassignTask($id);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json([
            'message' => 'Task successfully unassigned.',
            'status' => 200
        ]);
    }


}
