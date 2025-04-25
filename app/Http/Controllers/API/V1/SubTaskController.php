<?php

namespace App\Http\Controllers\API\V1;

use App\Exceptions\SubTaskCreationException;
use App\Exceptions\SubTaskUpdateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubTaskStoreRequest;
use App\Http\Requests\SubTaskUpdateRequest;
use App\Models\SubTask;
use App\Services\SubTaskService;
use Illuminate\Http\Request;

class SubTaskController extends Controller
{
    protected SubTaskService $subTaskService;

    public function __construct(SubTaskService $subTaskService)
    {
        $this->subTaskService = $subTaskService;
    }

    public function show($id)
    {
        $subTask = $this->subTaskService->findById($id);
        $this->authorize('view', $subTask);
        return $subTask;
    }

    public function findByTask($id, Request $request)
    {
        $perPage = (int) $request->query('perPage', 10);
        $data = $this->subTaskService->findByTask($id, $perPage);
        return response()->json(['message'=> 'SubTasks by Tasks', 'tasks'=> $data, 'status'=> 200]);

    }

    public function storeSubtask($id, SubTaskStoreRequest $request)
    {
        $this->authorize('create', SubTask::class);
        $data = $request->validated();
        $data['task_id'] = $id;
        $task = $this->subTaskService->create($data);
        return response()->json(['message' => 'SubTask successfully created', 'task' => $task, 'status' => 201], 201);
    }

    public function update($id, SubTaskUpdateRequest $request)
    {
        $data = $request->validated();
        $subTask = $this->subTaskService->findById($id);
        $this->authorize('update', $subTask);
        $task = $this->subTaskService->update($id, $data);
        return response()->json(['message' => 'SubTask successfully updated', 'task'=>$task, 'status'=>200]);
    }


    public function destroy($id)
    {
        $subTask = $this->subTaskService->findById($id);
        $this->authorize('delete', $subTask);

        $this->subTaskService->delete($id);

        return response()->json(['message' => 'SubTask deleted successfully', 'status' => 200]);
    }


    public function markAsComplete($id)
    {
        $task = $this->subTaskService->completeTask($id);
        if ($task) {
            return response()->json(['message' => 'SubTask marked as completed',
                'task' => $task,
                'status' => 200,
            ]);
        } else {
            return response()->json([
                'message' => 'Task already completed.',
            ], 400);
        }
    }

    public function assign($id, Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $result = $this->subTaskService->assignTask($id, $data);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json([
            'message' => 'Task successfully assigned to user',
            'status' => 200
        ]);
    }

    public function unassign($id){
        $result = $this->subTaskService->unassignTask($id);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json([
            'message' => 'Task successfully unassigned.',
            'status' => 200
        ]);
    }

}
