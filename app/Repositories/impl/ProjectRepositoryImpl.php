<?php

namespace App\Repositories\impl;

use App\Models\Project;
use App\Repositories\ProjectRepository;

class ProjectRepositoryImpl implements ProjectRepository
{

    public function all()
    {
        return Project::all();
    }

    public function create($data)
    {
        return Project::create($data);
    }

    public function find($id)
    {
        return Project::findOrFail($id);
    }

    public function update($id, $data)
    {
        $project = Project::findOrFail($id);
        $project->update($data);
        return $project;
    }

    public function delete($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();
    }

    public function assignMember($projectId, array $data)
    {
        // TODO: Implement assignMember() method.
    }

    public function unassignMember($projectId, array $data)
    {
        // TODO: Implement unassignMember() method.
    }
}
