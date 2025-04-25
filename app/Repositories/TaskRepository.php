<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskRepository
{
    public function findById(int $id);

    public function getByProject(int $projectId, int $perPage = 10): LengthAwarePaginator;

    public function create($data);

    public function update($id, $data);

    public function delete($id);

}
