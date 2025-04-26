<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function generateProjectReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $this->authorize('generate-report');

        $report = $this->reportService->generateProjectReport($validated);

        return response()->json($report);
    }

    public function exportProjectReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'project_id' => 'nullable|exists:projects,id'
        ]);

        $this->authorize('export-report');

        $report = $this->reportService->generateProjectReport($validated);

        $fileName = 'project-report-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new ReportExportService($report),
            $fileName,
            \Maatwebsite\Excel\Excel::XLSX,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
