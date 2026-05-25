<?php

namespace App\Services;

use App\Models\User;
use App\Models\Seizure;
use App\Models\VitalSign;
use App\Models\MedicalReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class MedicalReportService
{
    /**
     * Generate session report
     */
    public function generateSessionReport(User $user, $sessionId, $doctorId = null)
    {
        try {
            // Get session data
            $sessionData = DB::table('device_sessions')
                ->where('session_id', $sessionId)
                ->where('user_id', $user->id)
                ->first();

            if (!$sessionData) {
                throw new \Exception('Session not found');
            }

            // Get vital signs from session
            $vitalSigns = VitalSign::where('user_id', $user->id)
                ->whereBetween('created_at', [
                    $sessionData->started_at,
                    $sessionData->ended_at ?? now()
                ])
                ->get();

            // Get EEG data from session
            $eegData = DB::table('eeg_signals')
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [
                    $sessionData->started_at,
                    $sessionData->ended_at ?? now()
                ])
                ->get();

            // Get seizure events
            $seizures = Seizure::where('user_id', $user->id)
                ->whereBetween('created_at', [
                    $sessionData->started_at,
                    $sessionData->ended_at ?? now()
                ])
                ->get();

            // Calculate statistics
            $stats = $this->calculateSessionStatistics($vitalSigns, $eegData, $seizures);

            // Create report
            $report = MedicalReport::create([
                'user_id' => $user->id,
                'doctor_id' => $doctorId,
                'report_type' => 'session_report',
                'summary' => $this->generateSummary($stats, $sessionData),
                'vital_signs_data' => $vitalSigns->toArray(),
                'eeg_data' => $eegData->toArray(),
                'seizure_events' => $seizures->toArray(),
                'recommendations' => $this->generateRecommendations($stats),
                'generated_at' => now(),
            ]);

            // Generate PDF
            $this->generatePDF($report, $user);

            return $report;
        } catch (\Exception $e) {
            \Log::error('Report generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate daily report
     */
    public function generateDailyReport(User $user, $date = null, $doctorId = null)
    {
        try {
            $date = $date ? Carbon::parse($date) : now();
            $startDate = $date->copy()->startOfDay();
            $endDate = $date->copy()->endOfDay();

            // Get vital signs for the day
            $vitalSigns = VitalSign::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            // Get EEG data for the day
            $eegData = DB::table('eeg_signals')
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            // Get seizure events for the day
            $seizures = Seizure::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            // Calculate statistics
            $stats = $this->calculateDailyStatistics($vitalSigns, $eegData, $seizures);

            // Create report
            $report = MedicalReport::create([
                'user_id' => $user->id,
                'doctor_id' => $doctorId,
                'report_type' => 'daily_report',
                'summary' => $this->generateDailySummary($stats, $date),
                'vital_signs_data' => $vitalSigns->toArray(),
                'eeg_data' => $eegData->toArray(),
                'seizure_events' => $seizures->toArray(),
                'recommendations' => $this->generateRecommendations($stats),
                'generated_at' => now(),
            ]);

            // Generate PDF
            $this->generatePDF($report, $user);

            return $report;
        } catch (\Exception $e) {
            \Log::error('Daily report generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate weekly report
     */
    public function generateWeeklyReport(User $user, $weekStartDate = null, $doctorId = null)
    {
        try {
            $weekStartDate = $weekStartDate ? Carbon::parse($weekStartDate) : now()->startOfWeek();
            $weekEndDate = $weekStartDate->copy()->endOfWeek();

            // Get vital signs for the week
            $vitalSigns = VitalSign::where('user_id', $user->id)
                ->whereBetween('created_at', [$weekStartDate, $weekEndDate])
                ->get();

            // Get EEG data for the week
            $eegData = DB::table('eeg_signals')
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$weekStartDate, $weekEndDate])
                ->get();

            // Get seizure events for the week
            $seizures = Seizure::where('user_id', $user->id)
                ->whereBetween('created_at', [$weekStartDate, $weekEndDate])
                ->get();

            // Calculate statistics
            $stats = $this->calculateWeeklyStatistics($vitalSigns, $eegData, $seizures);

            // Create report
            $report = MedicalReport::create([
                'user_id' => $user->id,
                'doctor_id' => $doctorId,
                'report_type' => 'weekly_report',
                'summary' => $this->generateWeeklySummary($stats, $weekStartDate, $weekEndDate),
                'vital_signs_data' => $vitalSigns->toArray(),
                'eeg_data' => $eegData->toArray(),
                'seizure_events' => $seizures->toArray(),
                'recommendations' => $this->generateRecommendations($stats),
                'generated_at' => now(),
            ]);

            // Generate PDF
            $this->generatePDF($report, $user);

            return $report;
        } catch (\Exception $e) {
            \Log::error('Weekly report generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate session statistics
     */
    protected function calculateSessionStatistics($vitalSigns, $eegData, $seizures)
    {
        return [
            'session_duration' => $vitalSigns->count() * 0.5, // Assuming 0.5 seconds per reading
            'avg_heart_rate' => $vitalSigns->avg('heart_rate'),
            'max_heart_rate' => $vitalSigns->max('heart_rate'),
            'min_heart_rate' => $vitalSigns->min('heart_rate'),
            'avg_oxygen' => $vitalSigns->avg('oxygen_level'),
            'avg_temperature' => $vitalSigns->avg('temperature'),
            'avg_hrv' => $vitalSigns->avg('hrv'),
            'eeg_samples' => $eegData->count(),
            'seizure_count' => $seizures->count(),
            'seizure_detected' => $seizures->where('is_predicted', false)->count(),
            'seizure_predicted' => $seizures->where('is_predicted', true)->count(),
            'signal_quality' => $vitalSigns->avg('signal_quality'),
        ];
    }

    /**
     * Calculate daily statistics
     */
    protected function calculateDailyStatistics($vitalSigns, $eegData, $seizures)
    {
        return [
            'total_readings' => $vitalSigns->count(),
            'avg_heart_rate' => $vitalSigns->avg('heart_rate'),
            'max_heart_rate' => $vitalSigns->max('heart_rate'),
            'min_heart_rate' => $vitalSigns->min('heart_rate'),
            'avg_oxygen' => $vitalSigns->avg('oxygen_level'),
            'avg_temperature' => $vitalSigns->avg('temperature'),
            'avg_hrv' => $vitalSigns->avg('hrv'),
            'seizure_count' => $seizures->count(),
            'high_risk_periods' => $this->detectHighRiskPeriods($vitalSigns),
            'medication_compliance' => 'Good', // Can be calculated from data
            'sleep_quality' => 'Normal', // Can be calculated from HRV patterns
        ];
    }

    /**
     * Calculate weekly statistics
     */
    protected function calculateWeeklyStatistics($vitalSigns, $eegData, $seizures)
    {
        $dailyStats = [];
        
        for ($i = 0; $i < 7; $i++) {
            $date = now()->subDays(6 - $i)->startOfDay();
            $dailyVitals = $vitalSigns->filter(function ($v) use ($date) {
                return $v->created_at->startOfDay()->equalTo($date);
            });
            
            $dailyStats[$date->format('Y-m-d')] = [
                'avg_hr' => $dailyVitals->avg('heart_rate'),
                'seizures' => $seizures->filter(function ($s) use ($date) {
                    return $s->created_at->startOfDay()->equalTo($date);
                })->count(),
            ];
        }

        return [
            'total_readings' => $vitalSigns->count(),
            'avg_heart_rate' => $vitalSigns->avg('heart_rate'),
            'total_seizures' => $seizures->count(),
            'daily_breakdown' => $dailyStats,
            'trend' => $this->calculateTrend($vitalSigns),
            'compliance_score' => 0.85,
        ];
    }

    /**
     * Detect high-risk periods
     */
    protected function detectHighRiskPeriods($vitalSigns)
    {
        $highRiskPeriods = [];

        foreach ($vitalSigns as $vital) {
            if ($vital->heart_rate > 120 || $vital->oxygen_level < 90) {
                $highRiskPeriods[] = [
                    'time' => $vital->created_at,
                    'heart_rate' => $vital->heart_rate,
                    'oxygen' => $vital->oxygen_level,
                ];
            }
        }

        return $highRiskPeriods;
    }

    /**
     * Calculate trend
     */
    protected function calculateTrend($vitalSigns)
    {
        if ($vitalSigns->count() < 2) {
            return 'stable';
        }

        $first = $vitalSigns->first()->heart_rate;
        $last = $vitalSigns->last()->heart_rate;
        $change = (($last - $first) / $first) * 100;

        if ($change > 5) {
            return 'increasing';
        } elseif ($change < -5) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Generate summary
     */
    protected function generateSummary($stats, $sessionData)
    {
        $summary = "Session Report Summary\n";
        $summary .= "Session Duration: " . round($stats['session_duration'], 2) . " minutes\n";
        $summary .= "Average Heart Rate: " . round($stats['avg_heart_rate'], 1) . " BPM\n";
        $summary .= "Average Oxygen Level: " . round($stats['avg_oxygen'], 1) . "%\n";
        $summary .= "Seizure Events: " . $stats['seizure_count'] . "\n";
        $summary .= "Signal Quality: " . round($stats['signal_quality'], 1) . "%\n";

        return $summary;
    }

    /**
     * Generate daily summary
     */
    protected function generateDailySummary($stats, $date)
    {
        $summary = "Daily Report for " . $date->format('Y-m-d') . "\n";
        $summary .= "Total Readings: " . $stats['total_readings'] . "\n";
        $summary .= "Average Heart Rate: " . round($stats['avg_heart_rate'], 1) . " BPM\n";
        $summary .= "Heart Rate Range: " . round($stats['min_heart_rate'], 1) . " - " . round($stats['max_heart_rate'], 1) . " BPM\n";
        $summary .= "Average Oxygen Level: " . round($stats['avg_oxygen'], 1) . "%\n";
        $summary .= "Seizure Events: " . $stats['seizure_count'] . "\n";

        return $summary;
    }

    /**
     * Generate weekly summary
     */
    protected function generateWeeklySummary($stats, $startDate, $endDate)
    {
        $summary = "Weekly Report (" . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d') . ")\n";
        $summary .= "Total Readings: " . $stats['total_readings'] . "\n";
        $summary .= "Average Heart Rate: " . round($stats['avg_heart_rate'], 1) . " BPM\n";
        $summary .= "Total Seizures: " . $stats['total_seizures'] . "\n";
        $summary .= "Trend: " . $stats['trend'] . "\n";
        $summary .= "Compliance Score: " . round($stats['compliance_score'] * 100, 1) . "%\n";

        return $summary;
    }

    /**
     * Generate recommendations
     */
    protected function generateRecommendations($stats)
    {
        $recommendations = [];

        if ($stats['avg_heart_rate'] > 100) {
            $recommendations[] = 'Heart rate is elevated. Consider consulting with your doctor.';
        }

        if (isset($stats['avg_oxygen']) && $stats['avg_oxygen'] < 95) {
            $recommendations[] = 'Oxygen levels are low. Ensure proper ventilation and rest.';
        }

        if (isset($stats['seizure_count']) && $stats['seizure_count'] > 0) {
            $recommendations[] = 'Seizure events detected. Review medication and triggers.';
        }

        if (isset($stats['avg_hrv']) && $stats['avg_hrv'] < 30) {
            $recommendations[] = 'Heart rate variability is low. Increase relaxation activities.';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Vital signs are within normal range. Continue current routine.';
        }

        return $recommendations;
    }

    /**
     * Generate PDF report
     */
    protected function generatePDF(MedicalReport $report, User $user)
    {
        try {
            $pdf = PDF::loadView('reports.medical-report', [
                'report' => $report,
                'user' => $user,
                'generatedAt' => now(),
            ]);

            $filename = 'report_' . $report->id . '_' . now()->format('Y-m-d-H-i-s') . '.pdf';
            $path = 'reports/' . $filename;

            Storage::disk('local')->put($path, $pdf->output());

            // Update report with PDF path
            $report->update(['pdf_path' => $path]);

            return $path;
        } catch (\Exception $e) {
            \Log::error('PDF generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export report as CSV
     */
    public function exportAsCSV(MedicalReport $report)
    {
        try {
            $csv = "Medical Report Export\n";
            $csv .= "Report Type: " . $report->report_type . "\n";
            $csv .= "Generated: " . $report->generated_at . "\n\n";

            $csv .= "Summary:\n";
            $csv .= $report->summary . "\n\n";

            $csv .= "Recommendations:\n";
            foreach ($report->recommendations as $rec) {
                $csv .= "- " . $rec . "\n";
            }

            $filename = 'report_' . $report->id . '.csv';
            Storage::disk('local')->put('reports/' . $filename, $csv);

            return 'reports/' . $filename;
        } catch (\Exception $e) {
            \Log::error('CSV export error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get report by ID
     */
    public function getReport($reportId)
    {
        return MedicalReport::findOrFail($reportId);
    }

    /**
     * Get user reports
     */
    public function getUserReports(User $user, $limit = 10)
    {
        return MedicalReport::where('user_id', $user->id)
            ->orderBy('generated_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Delete report
     */
    public function deleteReport($reportId)
    {
        $report = MedicalReport::findOrFail($reportId);
        
        if ($report->pdf_path) {
            Storage::disk('local')->delete($report->pdf_path);
        }

        $report->delete();

        return true;
    }
}
