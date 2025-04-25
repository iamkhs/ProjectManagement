<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    protected ProjectService $projectService;

    public function __construct(ProjectService $projectService){
        $this->projectService = $projectService;
    }

    public function index()
    {
        $this->authorize('viewAny', Project::class); // Only admin & team_leader
        return $this->projectService->findAll();
    }

    public function store(ProjectStoreRequest $request)
    {
        $this->authorize('create', Project::class); // Only admin & team_leader

        $data = $request->validated();
        $this->projectService->create($data);
        return response()->json(['message' => 'Project created successfully.', 'status' => 201], 201);
    }

    public function show($id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('view', $project); // Admin, team_leader, or member
        return $this->projectService->findById($id);
    }

    public function update($id, ProjectUpdateRequest $request)
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project); // Admin, or team_leader if they created it

        $data = $request->validated();
        $this->projectService->update($id, $data);
        return response()->json(['message' => 'Project updated successfully.', 'status' => 200]);
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('delete', $project); // Admin, or team_leader if they created it

        $this->projectService->delete($id);
        return response()->json(['message' => 'Project deleted successfully.', 'status' => 200]);
    }

    public function assignMember($projectId, Request $request)
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('assign', $project); // Only admin & team_leader

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $projectMember = $this->projectService->assign($projectId, $data);
        if ($projectMember) {
            return response()->json(['message' => 'Project member assigned successfully.', 'status' => 200]);
        }

        return response()->json(['message' => 'User is already a member of this project'], 400);
    }

    public function unassignMember($projectId, Request $request)
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('unassign', $project); // Only admin & team_leader

        $data = $request->validate([
            'user_id' => 'required|exists:project_members,user_id',
        ]);

        $isDeleted = $this->projectService->unassign($projectId, $data);
        if ($isDeleted) {
            return response()->json(['message' => 'Project member unassigned successfully.', 'status' => 200]);
        }

        return response()->json(['message' => 'Failed to unassign member'], 400);
    }
}
