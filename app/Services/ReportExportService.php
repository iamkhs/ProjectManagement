<?php

namespace App\Services;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportExportService implements WithMultipleSheets
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function sheets(): array
    {
        return [
            new ProjectSummarySheet($this->reportData),
            new TasksSheet($this->reportData),
            new SubtasksSheet($this->reportData),
            new PerformanceSheet($this->reportData)
        ];
    }
}

class ProjectSummarySheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        // Convert all relevant arrays to Collections
        $this->reportData = [
            'meta' => $reportData['meta'] ?? [],
            'projects' => collect($reportData['projects'] ?? []),
            'performance_breakdown' => [
                'by_team_leaders' => collect($reportData['performance_breakdown']['by_team_leaders'] ?? []),
                'by_team_members' => collect($reportData['performance_breakdown']['by_team_members'] ?? [])
            ],
            'completion_metrics' => $reportData['completion_metrics'] ?? [
                    'by_tasks' => ['total' => 0, 'completed' => 0, 'completion_percentage' => 0],
                    'by_subtasks' => ['total' => 0, 'completed' => 0, 'completion_percentage' => 0]
                ]
        ];
    }

    public function array(): array
    {
        $summary = $this->reportData['completion_metrics'];
        $performance = $this->reportData['performance_breakdown'];

        return [
            ['PROJECT PERFORMANCE REPORT'],
            ['Generated At', $this->reportData['meta']['generated_at']],
            ['Date Range',
                ($this->reportData['meta']['date_range']['start'] ?? 'All Time') . ' to ' .
                ($this->reportData['meta']['date_range']['end'] ?? 'Present')
            ],
            [],
            ['SUMMARY METRICS'],
            ['Total Projects', count($this->reportData['projects'])],
            ['Total Tasks', $summary['by_tasks']['total']],
            ['Completed Tasks', $summary['by_tasks']['completed']],
            ['Task Completion %', $summary['by_tasks']['completion_percentage'] . '%'],
            ['Total Subtasks', $summary['by_subtasks']['total']],
            ['Completed Subtasks', $summary['by_subtasks']['completed']],
            ['Subtask Completion %', $summary['by_subtasks']['completion_percentage'] . '%'],
            ['Avg. Task Completion Time (hours)', $summary['by_tasks']['average_completion_time_hours'] ?? 'N/A'],
            ['Avg. Subtask Completion Time (hours)', $summary['by_subtasks']['average_completion_time_hours'] ?? 'N/A'],
            [],
            ['TEAM LEADER PERFORMANCE'],
            ...$performance['by_team_leaders']->map(function ($leader) {
                return [
                    $leader['team_leader_name'],
                    $leader['projects_managed'],
                    $leader['subtasks_under_management'],
                    $leader['completed_subtasks_percentage'] . '%'
                ];
            })->prepend(['Name', 'Projects', 'Subtasks Managed', 'Completion %']),
            [],
            ['TEAM MEMBER PERFORMANCE'],
            ...$performance['by_team_members']->map(function ($member) {
                return [
                    $member['member_name'],
                    $member['assigned_subtasks'],
                    $member['completed_subtasks'],
                    $member['completion_percentage'] . '%',
                    $member['average_completion_time_hours'] ?? 'N/A'
                ];
            })->prepend(['Name', 'Assigned Subtasks', 'Completed', 'Completion %', 'Avg. Time (hours)'])
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            5 => ['font' => ['bold' => true]],
            16 => ['font' => ['bold' => true]],
            'A1:B1' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2']
                ]
            ],
            'A5:A13' => ['font' => ['bold' => true]],
            'A16:D16' => ['font' => ['bold' => true]],
            'A23:E23' => ['font' => ['bold' => true]],
            'A2:B13' => ['borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]]]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 25,
            'C' => 20,
            'D' => 15,
            'E' => 20
        ];
    }
}

class TasksSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = [
            'projects' => collect($reportData['projects'] ?? [])
        ];
    }

    public function array(): array
    {
        $header = [
            ['TASKS DETAILED REPORT'],
            [],
            [
                'Task ID',
                'Project',
                'Title',
                'Status',
                'Assigned To',
                'Assigned By',
                'Due Date',
                'Created At',
                'Completed At',
                'Completion Time (hours)',
                'Overdue',
                'Total Subtasks',
                'Completed Subtasks',
                'Subtask Completion %'
            ]
        ];

        $rows = collect($this->reportData['projects'])->flatMap(function ($project) {
            return $project['tasks']->map(function ($task) use ($project) {
                return [
                    $task['task_id'],
                    $project['project_title'],
                    $task['title'],
                    ucfirst($task['status']),
                    $task['assigned_to']['name'] ?? 'Unassigned',
                    $task['assigned_by']['name'] ?? 'System',
                    $task['due_date'] ?? 'N/A',
                    $task['created_at'],
                    $task['completed_at'] ?? 'Incomplete',
                    $task['completion_time_hours'] ?? 'N/A',
                    $task['is_overdue'] ? 'Yes' : 'No',

                ];
            });
        });

        return array_merge($header, $rows->toArray());
    }

    public function title(): string
    {
        return 'Tasks';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            3 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6']
                ]
            ],
            'A' => ['alignment' => ['wrapText' => true]],
            'C' => ['alignment' => ['wrapText' => true]],
            'K' => [
                'font' => [
                    'color' => ['rgb' => 'FF0000']
                ]
            ],
            'J' => [
                'numberFormat' => [
                    'formatCode' => '0.00'
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // Task ID
            'B' => 20,  // Project
            'C' => 30,  // Title
            'D' => 12,  // Status
            'E' => 20,  // Assigned To
            'F' => 20,  // Assigned By
            'G' => 15,  // Due Date
            'H' => 20,  // Created At
            'I' => 20,  // Completed At
            'J' => 18,  // Completion Time
            'K' => 10,  // Overdue
            'L' => 12,  // Total Subtasks
            'M' => 15,  // Completed Subtasks
            'N' => 18   // Completion %
        ];
    }
}

class SubtasksSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = [
            'projects' => collect($reportData['projects'] ?? [])
        ];
    }

    public function array(): array
    {
        $header = [
            ['SUBTASKS DETAILED REPORT'],
            [],
            [
                'Subtask ID',
                'Parent Task ID',
                'Project',
                'Title',
                'Status',
                'Assigned To',
                'Created At',
                'Completed At',
                'Completion Time (hours)',
                'Overdue'
            ]
        ];

        $rows = collect($this->reportData['projects'])->flatMap(function ($project) {
            return $project['tasks']->flatMap(function ($task) use ($project) {
                return $task['subtasks']->map(function ($subtask) use ($task, $project) {
                    return [
                        $subtask['subtask_id'],
                        $task['task_id'],
                        $project['project_title'],
                        $subtask['title'],
                        ucfirst($subtask['status']),
                        $subtask['assigned_to']['name'] ?? 'Unassigned',
                        $subtask['created_at'],
                        $subtask['completed_at'] ?? 'Incomplete',
                        $subtask['completion_time_hours'] ?? 'N/A',
                        $subtask['is_overdue'] ? 'Yes' : 'No'
                    ];
                });
            });
        });

        return array_merge($header, $rows->toArray());
    }

    public function title(): string
    {
        return 'Subtasks';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            3 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6']
                ]
            ],
            'I' => [
                'numberFormat' => [
                    'formatCode' => '0.00'
                ]
            ],
            'J' => [
                'font' => [
                    'color' => ['rgb' => $this->getOverdueColor($sheet)]
                ]
            ]
        ];
    }

    protected function getOverdueColor(Worksheet $sheet): string
    {
        $highestRow = $sheet->getHighestRow();
        for ($row = 4; $row <= $highestRow; $row++) {
            if ($sheet->getCell('J'.$row)->getValue() === 'Yes') {
                return 'FF0000'; // Red for overdue
            }
        }
        return '000000'; // Black if no overdue items
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Subtask ID
            'B' => 12,  // Parent Task ID
            'C' => 20,  // Project
            'D' => 30,  // Title
            'E' => 12,  // Status
            'F' => 20,  // Assigned To
            'G' => 20,  // Created At
            'H' => 20,  // Completed At
            'I' => 18,  // Completion Time
            'J' => 10   // Overdue
        ];
    }
}

class PerformanceSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = [
            'performance_breakdown' => [
                'by_team_leaders' => collect($reportData['performance_breakdown']['by_team_leaders'] ?? []),
                'by_team_members' => collect($reportData['performance_breakdown']['by_team_members'] ?? [])
            ],
            'completion_metrics' => $reportData['completion_metrics'] ?? []
        ];
    }

    public function array(): array
    {
        $performance = $this->reportData['performance_breakdown'];

        return [
            ['TEAM PERFORMANCE METRICS'],
            [],
            ['TEAM LEADERS PERFORMANCE'],
            [
                'Team Leader',
                'Projects Managed',
                'Total Tasks',
                'Total Subtasks',
                'Subtask Completion %',
                'Avg. Completion Time (hours)'
            ],
            ...$performance['by_team_leaders']->map(function ($leader) {
                return [
                    $leader['team_leader_name'],
                    $leader['projects_managed'],
                    $leader['tasks_under_management'],
                    $leader['subtasks_under_management'],
                    $leader['completed_subtasks_percentage'] / 100, // For percentage format
                    $this->reportData['completion_metrics']['by_subtasks']['average_completion_time_hours'] ?? 'N/A'
                ];
            }),
            [],
            ['TEAM MEMBERS PERFORMANCE'],
            [
                'Member Name',
                'Assigned Subtasks',
                'Completed Subtasks',
                'Completion %',
                'Avg. Completion Time (hours)',
                'Efficiency Rating'
            ],
            ...$performance['by_team_members']->map(function ($member) {
                $rating = $this->calculateEfficiencyRating(
                    $member['completion_percentage'],
                    $member['average_completion_time_hours']
                );

                return [
                    $member['member_name'],
                    $member['assigned_subtasks'],
                    $member['completed_subtasks'],
                    $member['completion_percentage'] / 100, // For percentage format
                    $member['average_completion_time_hours'] ?? 'N/A',
                    $rating
                ];
            })
        ];
    }

    protected function calculateEfficiencyRating(float $completionPercent, ?float $avgTime): string
    {
        if ($avgTime === null) return 'N/A';

        $score = ($completionPercent / 100) * (1 / max(1, $avgTime));

        if ($score > 0.8) return 'Excellent';
        if ($score > 0.6) return 'Good';
        if ($score > 0.4) return 'Average';
        return 'Needs Improvement';
    }

    public function title(): string
    {
        return 'Performance';
    }

    public function styles(Worksheet $sheet)
    {
        $teamLeadersCount = count($this->reportData['performance_breakdown']['by_team_leaders']);
        $teamMembersCount = count($this->reportData['performance_breakdown']['by_team_members']);

        $sheet->getStyle('D4:D' . (4 + $teamLeadersCount))
            ->getNumberFormat()
            ->setFormatCode('0.00%');

        // Format team members percentage cells
        $teamMembersStartRow = 6 + $teamLeadersCount;
        $sheet->getStyle('D' . $teamMembersStartRow . ':D' . ($teamMembersStartRow + $teamMembersCount))
            ->getNumberFormat()
            ->setFormatCode('0.00%');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            3 => ['font' => ['bold' => true]],
            4 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6']
                ]
            ],
            $teamMembersStartRow - 1 => ['font' => ['bold' => true]],
            $teamMembersStartRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6']
                ]
            ],
            'F' => [
                'numberFormat' => [
                    'formatCode' => '0.00'
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]

            ],
            'F4:F' . (4 + $teamLeadersCount) => [
                'numberFormat' => [
                    'formatCode' => '0.00'
                ]
            ],
            'F' . $teamMembersStartRow . ':F' . ($teamMembersStartRow + $teamMembersCount) => [
                'numberFormat' => [
                    'formatCode' => '0.00'
                ]
            ],
        ];
    }


    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Names
            'B' => 18,  // Projects/Tasks
            'C' => 15,  // Counts
            'D' => 18,  // Percentage
            'E' => 22,  // Avg Time
            'F' => 20   // Rating
        ];
    }
}
