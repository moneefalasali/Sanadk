<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MedicalReportService;
use App\Models\MedicalReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MedicalReportController extends Controller
{
    protected $reportService;

    public function __construct(MedicalReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Generate session report
     */
    public function generateSessionReport(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'session_id' => 'required|string',
            ]);

            $report = $this->reportService->generateSessionReport(
                $user,
                $validated['session_id'],
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Session report generated successfully',
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            Log::error('Session report generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating session report',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Generate daily report
     */
    public function generateDailyReport(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'date' => 'nullable|date',
            ]);

            $report = $this->reportService->generateDailyReport(
                $user,
                $validated['date'] ?? null,
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Daily report generated successfully',
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            Log::error('Daily report generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating daily report',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Generate weekly report
     */
    public function generateWeeklyReport(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'week_start_date' => 'nullable|date',
            ]);

            $report = $this->reportService->generateWeeklyReport(
                $user,
                $validated['week_start_date'] ?? null,
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Weekly report generated successfully',
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            Log::error('Weekly report generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating weekly report',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get report by ID
     */
    public function getReport($reportId)
    {
        try {
            $user = Auth::user();
            $report = MedicalReport::where('id', $reportId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            Log::error('Get report error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get user reports
     */
    public function getUserReports(Request $request)
    {
        try {
            $user = Auth::user();
            $limit = $request->input('limit', 10);

            $reports = $this->reportService->getUserReports($user, $limit);

            return response()->json([
                'success' => true,
                'reports' => $reports,
            ]);
        } catch (\Exception $e) {
            Log::error('Get user reports error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving reports',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Export report as CSV
     */
    public function exportAsCSV($reportId)
    {
        try {
            $user = Auth::user();
            $report = MedicalReport::where('id', $reportId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $csvPath = $this->reportService->exportAsCSV($report);

            return response()->json([
                'success' => true,
                'message' => 'Report exported as CSV',
                'csv_path' => $csvPath,
            ]);
        } catch (\Exception $e) {
            Log::error('CSV export error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error exporting report',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download PDF report
     */
    public function downloadPDF($reportId)
    {
        try {
            $user = Auth::user();
            $report = MedicalReport::where('id', $reportId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            if (!$report->pdf_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF not available',
                ], 404);
            }

            return response()->download(storage_path('app/' . $report->pdf_path));
        } catch (\Exception $e) {
            Log::error('PDF download error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error downloading PDF',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete report
     */
    public function deleteReport($reportId)
    {
        try {
            $user = Auth::user();
            $report = MedicalReport::where('id', $reportId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $this->reportService->deleteReport($reportId);

            return response()->json([
                'success' => true,
                'message' => 'Report deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete report error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting report',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
