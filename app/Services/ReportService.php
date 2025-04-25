<?php

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{

    public function generateProjectReport(array $filters): array
    {
        $projects = $this->getProjectsWithTasksAndSubtasks($filters);

        return [
            'meta' => $this->getReportMeta($filters),
            'projects' => $this->processProjects($projects),
            'performance_breakdown' => $this->generatePerformanceBreakdown($projects),
            'completion_metrics' => $this->calculateCompletionMetrics($projects)
        ];
    }

    protected function getProjectsWithTasksAndSubtasks(array $filters): Collection
    {
        return Project::with([
            'teamLeader:id,name',
            'tasks' => function ($query) use ($filters) {
                $query->when($filters['start_date'] ?? false, function ($q) use ($filters) {
                    $q->where('tasks.created_at', '>=', $filters['start_date']);
                })
                    ->when($filters['end_date'] ?? false, function ($q) use ($filters) {
                        $q->where('tasks.created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
                    })
                    ->with([
                        'subtasks' => function ($query) {
                            $query->orderBy('created_at');
                        },
                        'assignedTo:id,name',
                        'assignedBy:id,name'
                    ]);
            },
            'teamMembers:id,name'
        ])
            ->when($filters['project_id'] ?? false, function ($q) use ($filters) {
                $q->where('id', $filters['project_id']);
            })
            ->get();
    }

    protected function getReportMeta(array $filters): array
    {
        return [
            'report_type' => 'project_performance',
            'generated_at' => now()->toDateTimeString(),
            'date_range' => [
                'start' => $filters['start_date'] ?? null,
                'end' => $filters['end_date'] ?? null
            ],
            'filters_applied' => array_filter($filters)
        ];
    }

    protected function processProjects(Collection $projects): array
    {
        return $projects->map(function ($project) {
            $tasks = $project->tasks;

            return [
                'project_id' => $project->id,
                'project_title' => $project->title,
                'team_leader' => $project->teamLeader,
                'tasks' => $tasks->map(function ($task) {
                    return [
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'status' => $task->status,
                        'assigned_to' => $task->assignedTo,
                        'assigned_by' => $task->assignedBy,
                        'due_date' => $task->due_date?->toDateString(),
                        'created_at' => $task->created_at->toDateTimeString(),
                        'completed_at' => $task->completed_at?->toDateTimeString(),
                        'completion_time_hours' => $this->calculateCompletionTime($task),
                        'is_overdue' => $this->isOverdue($task),
                        'subtasks' => $task->subtasks->map(function ($subtask) {
                            return [
                                'subtask_id' => $subtask->id,
                                'title' => $subtask->title,
                                'status' => $subtask->status,
                                'assigned_to' => $subtask->assignedTo,
                                'created_at' => $subtask->created_at->toDateTimeString(),
                                'completed_at' => $subtask->completed_at?->toDateTimeString(),
                                'completion_time_hours' => $this->calculateCompletionTime($subtask),
                                'is_overdue' => $this->isOverdue($subtask)
                            ];
                        })
                    ];
                })
            ];
        })->toArray();
    }

    protected function calculateCompletionMetrics(Collection $projects): array
    {
        $allSubtasks = $projects->pluck('tasks.*.subtasks')->flatten();
        $allTasks = $projects->pluck('tasks')->flatten();

        return [
            'by_subtasks' => [
                'total' => $allSubtasks->count(),
                'completed' => $allSubtasks->where('status', 'completed')->count(),
                'completion_percentage' => $allSubtasks->count() > 0
                    ? round(($allSubtasks->where('status', 'completed')->count() / $allSubtasks->count()) * 100, 2)
                    : 0,
                'average_completion_time_hours' => $this->calculateAverageCompletionTime($allSubtasks)
            ],
            'by_tasks' => [
                'total' => $allTasks->count(),
                'completed' => $allTasks->where('status', 'completed')->count(),
                'completion_percentage' => $allTasks->count() > 0
                    ? round(($allTasks->where('status', 'completed')->count() / $allTasks->count()) * 100, 2)
                    : 0,
                'average_completion_time_hours' => $this->calculateAverageCompletionTime($allTasks)
            ]
        ];
    }

    protected function generatePerformanceBreakdown(Collection $projects): array
    {
        $allTasks = $projects->pluck('tasks')->flatten();
        $allTeamMembers = $projects->pluck('teamMembers')->flatten()->unique('id');

        return [
            'by_team_leaders' => $projects->map(function ($project) {
                $tasks = $project->tasks;
                return [
                    'team_leader_id' => $project->team_leader_id,
                    'team_leader_name' => $project->teamLeader->name,
                    'projects_managed' => 1, // Will be summed later
                    'tasks_under_management' => $tasks->count(),
                    'subtasks_under_management' => $tasks->pluck('subtasks')->flatten()->count(),
                    'completed_subtasks_percentage' => $this->calculateSubtaskCompletionPercentage($tasks)
                ];
            })->groupBy('team_leader_id')->map(function ($leaders) {
                return [
                    'team_leader_id' => $leaders->first()['team_leader_id'],
                    'team_leader_name' => $leaders->first()['team_leader_name'],
                    'projects_managed' => $leaders->count(),
                    'tasks_under_management' => $leaders->sum('tasks_under_management'),
                    'subtasks_under_management' => $leaders->sum('subtasks_under_management'),
                    'completed_subtasks_percentage' => $leaders->avg('completed_subtasks_percentage')
                ];
            })->values()->toArray(),

            'by_team_members' => $allTeamMembers->map(function ($member) use ($allTasks) {
                $memberSubtasks = $allTasks->pluck('subtasks')
                    ->flatten()
                    ->where('assigned_to.id', $member->id);

                return [
                    'member_id' => $member->id,
                    'member_name' => $member->name,
                    'assigned_subtasks' => $memberSubtasks->count(),
                    'completed_subtasks' => $memberSubtasks->where('status', 'completed')->count(),
                    'completion_percentage' => $memberSubtasks->count() > 0
                        ? round(($memberSubtasks->where('status', 'completed')->count() / $memberSubtasks->count()) * 100, 2)
                        : 0,
                    'average_completion_time_hours' => $this->calculateAverageCompletionTime($memberSubtasks)
                ];
            })->toArray()
        ];
    }

    protected function calculateSubtaskCompletionPercentage(Collection $tasks): float
    {
        $subtasks = $tasks->pluck('subtasks')->flatten();
        if ($subtasks->isEmpty()) return 0.0;

        return round(
            ($subtasks->where('status', 'completed')->count() / $subtasks->count()) * 100,
            2
        );
    }

    protected function calculateCompletionTime($item): ?float
    {
        if (!$item->completed_at) return null;
        return round($item->created_at->diffInHours($item->completed_at), 2);
    }

    protected function calculateAverageCompletionTime(Collection $items): ?float
    {
        $times = $items->map(function ($item) {
            return $this->calculateCompletionTime($item);
        })->filter()->values();

        return $times->isNotEmpty() ? round($times->avg(), 2) : null;
    }

    protected function isOverdue($item): bool
    {
        return $item->due_date
            && !$item->completed_at
            && now()->greaterThan($item->due_date);
    }
}
