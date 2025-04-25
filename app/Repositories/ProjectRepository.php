<?php

namespace App\Repositories;

interface ProjectRepository
{
    public function all();
    public function find($id);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
    public function assignMember($projectId, array $data);
    public function unassignMember($projectId, array $data);
}
