<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SubTaskRepository
{
    public function findById(int $id);

    public function getByTask(int $taskId, int $perPage = 10): LengthAwarePaginator;

    public function create($data);

    public function update($id, $data);

    public function delete($id);
}
