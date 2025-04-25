<?php

namespace App\Providers;

use App\Repositories\impl\ProjectRepositoryImpl;
use App\Repositories\impl\SubTaskRepositoryImpl;
use App\Repositories\impl\TaskRepositoryImpl;
use App\Repositories\ProjectRepository;
use App\Repositories\SubTaskRepository;
use App\Repositories\TaskRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProjectRepository::class, ProjectRepositoryImpl::class);
        $this->app->bind(TaskRepository::class, TaskRepositoryImpl::class);
        $this->app->bind(SubTaskRepository::class, SubTaskRepositoryImpl::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
