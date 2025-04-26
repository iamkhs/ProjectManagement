<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Project;
use App\Models\SubTask;
use App\Models\Task;
use App\Policies\ProjectPolicy;
use App\Policies\ReportPolicy;
use App\Policies\SubtaskPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Project::class => ProjectPolicy::class,
        Task::class => TaskPolicy::class,
        SubTask::class => SubTaskPolicy::class,
         'report' => ReportPolicy::class

    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('generate-report', [ReportPolicy::class, 'generateReport']);
        Gate::define('export-report', [ReportPolicy::class, 'exportReport']);

    }
}
