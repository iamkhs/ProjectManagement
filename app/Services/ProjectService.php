<?php

namespace App\Services;

use App\Exceptions\ProjectCreationException;
use App\Exceptions\ProjectUpdateException;
use App\Models\ProjectMember;
use App\Repositories\ProjectRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;


class ProjectService
{
    protected ProjectRepository $projectRepo;

    public function __construct(ProjectRepository $projectRepo)
    {
        $this->projectRepo = $projectRepo;
    }


    public function create($request)
    {

        try {
            $request['team_leader_id'] = auth()->user()->id;
            return $this->projectRepo->create($request);
        } catch (Exception $e) {
            Log::error('Project creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);

            throw new ProjectCreationException();
        }
    }

    public function findAll()
    {
        return $this->projectRepo->all();
    }

    public function findById($id)
    {
        return $this->projectRepo->find($id);
    }

    public function update($id, $data)
    {
        try {
            $this->projectRepo->update($id, $data);
        } catch (Exception $e) {
            Log::error('Project update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
            throw new ProjectUpdateException();
        }
    }

    public function delete($id)
    {
        try {
            $this->projectRepo->delete($id);
        }catch (Exception $e){
            Log::error('Project deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
            throw new ModelNotFoundException($e->getMessage());
        }
    }

    public function assign($id, $data){

        $existingMember = ProjectMember::where('project_id', $id)
            ->where('user_id', $data['user_id'])
            ->first();
        if ($existingMember) {
            return null;
        }

        return ProjectMember::create([
            'project_id' => $id,
            'user_id' => $data['user_id'],
        ]);
    }

    public function unassign($id,  $data){
        $existingMember = ProjectMember::where('project_id', $id)
            ->where('user_id', $data['user_id'])
            ->first();
        if ($existingMember) {
            $existingMember->delete();
            return true;
        }
        return false;
    }
}
