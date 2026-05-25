<?php

namespace App\Services;

use App\Models\VitalSign;
use App\Models\User;
use App\Models\Seizure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MedicalDeviceService
{
    /**
     * Process Polar device data (Heart Rate, RR intervals, HRV)
     */
    public function processPolarData(User $user, array $data)
    {
        try {
            $vitalSign = VitalSign::create([
                'user_id' => $user->id,
                'heart_rate' => $data['heart_rate'] ?? null,
                'rr_interval' => $data['rr_interval'] ?? null,
                'hrv' => $data['hrv'] ?? null,
                'device_type' => 'polar',
                'device_id' => $data['device_id'] ?? null,
                'battery_level' => $data['battery_level'] ?? null,
                'signal_quality' => $data['signal_quality'] ?? 100,
                'timestamp' => Carbon::now(),
            ]);

            // Analyze for seizure prediction
            $analysis = $this->analyzeVitalSigns($user, $vitalSign);

            // Return processed result for queued jobs to broadcast
            return [
                'vital_sign' => $vitalSign,
                'analysis' => $analysis,
            ];
        } catch (\Exception $e) {
            Log::error('Polar data processing error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process Emotiv EEG data (Brain signals)
     */
    public function processEmotivEEGData(User $user, array $data)
    {
        try {
            $eegData = [
                'user_id' => $user->id,
                'af3' => $data['AF3'] ?? null,
                'af4' => $data['AF4'] ?? null,
                'f3' => $data['F3'] ?? null,
                'f4' => $data['F4'] ?? null,
                'fc5' => $data['FC5'] ?? null,
                'fc6' => $data['FC6'] ?? null,
                'p7' => $data['P7'] ?? null,
                'p8' => $data['P8'] ?? null,
                'o1' => $data['O1'] ?? null,
                'o2' => $data['O2'] ?? null,
                'device_type' => 'emotiv',
                'session_id' => $data['session_id'] ?? null,
                'timestamp' => Carbon::now(),
            ];

            // Store EEG data (create custom table if needed)
            $eegRecord = \DB::table('eeg_signals')->insert($eegData);

            // Analyze EEG for seizure patterns
            $analysis = $this->analyzeEEGSignals($user, $data);

            // Return processed result for queued jobs to broadcast
            return [
                'eeg_data' => $data,
                'analysis' => $analysis,
            ];
        } catch (\Exception $e) {
            Log::error('Emotiv EEG processing error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process ESP32 IoT sensor data
     */
    public function processESP32Data(User $user, array $data)
    {
        try {
            $vitalSign = VitalSign::create([
                'user_id' => $user->id,
                'heart_rate' => $data['heart_rate'] ?? null,
                'oxygen_level' => $data['oxygen_level'] ?? null,
                'temperature' => $data['temperature'] ?? null,
                'blood_pressure_systolic' => $data['bp_systolic'] ?? null,
                'blood_pressure_diastolic' => $data['bp_diastolic'] ?? null,
                'device_type' => 'esp32',
                'device_id' => $data['device_id'] ?? null,
                'signal_quality' => $data['signal_quality'] ?? 100,
                'timestamp' => Carbon::now(),
            ]);

            // Analyze vital signs
            $analysis = $this->analyzeVitalSigns($user, $vitalSign);

            // Return processed result for queued jobs to broadcast
            return [
                'vital_sign' => $vitalSign,
                'analysis' => $analysis,
            ];
        } catch (\Exception $e) {
            Log::error('ESP32 data processing error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Analyze vital signs for seizure prediction
     */
    public function analyzeVitalSigns(User $user, VitalSign $vitalSign)
    {
        $analysis = [
            'seizure_detected' => false,
            'prediction_score' => 0,
            'alert_level' => 'normal',
            'recommendations' => [],
        ];

        // Get recent vital signs for trend analysis
        $recentSigns = VitalSign::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $avgHeartRate = $recentSigns->avg('heart_rate');
        $avgOxygen = $recentSigns->avg('oxygen_level');

        // Seizure detection logic
        if ($vitalSign->heart_rate > 130 && $vitalSign->oxygen_level < 90) {
            $analysis['seizure_detected'] = true;
            $analysis['alert_level'] = 'emergency';
            $analysis['prediction_score'] = 0.95;

            // Create seizure record
            Seizure::create([
                'user_id' => $user->id,
                'start_time' => now(),
                'is_predicted' => false,
                'type' => 'detected',
                'notes' => 'Detected from vital signs',
            ]);

            $analysis['recommendations'] = [
                'Immediate medical attention required',
                'Contact emergency services',
                'Notify family members',
            ];
        } elseif ($vitalSign->heart_rate > 110 || $recentSigns->avg('heart_rate') > 100) {
            $analysis['alert_level'] = 'warning';
            $analysis['prediction_score'] = 0.65;
            $analysis['recommendations'] = [
                'Monitor vital signs closely',
                'Reduce physical activity',
                'Stay in safe location',
            ];
        }

        // Cache analysis for real-time dashboard
        Cache::put("user_{$user->id}_analysis", $analysis, now()->addMinutes(5));

        return $analysis;
    }

    /**
     * Analyze EEG signals for seizure patterns
     */
    public function analyzeEEGSignals(User $user, array $eegData)
    {
        $analysis = [
            'seizure_risk' => 'low',
            'risk_score' => 0,
            'abnormal_channels' => [],
            'recommendations' => [],
        ];

        // Simple EEG analysis (can be enhanced with ML models)
        $channels = ['AF3', 'AF4', 'F3', 'F4', 'FC5', 'FC6', 'P7', 'P8', 'O1', 'O2'];
        $abnormalCount = 0;

        foreach ($channels as $channel) {
            $value = $eegData[$channel] ?? 0;
            // Abnormal if signal is too high (simplified logic)
            if (abs($value) > 80) {
                $abnormalCount++;
                $analysis['abnormal_channels'][] = $channel;
            }
        }

        if ($abnormalCount >= 5) {
            $analysis['seizure_risk'] = 'high';
            $analysis['risk_score'] = 0.8;
            $analysis['recommendations'] = [
                'High seizure risk detected',
                'Notify healthcare provider',
                'Prepare emergency protocols',
            ];
        } elseif ($abnormalCount >= 3) {
            $analysis['seizure_risk'] = 'moderate';
            $analysis['risk_score'] = 0.5;
            $analysis['recommendations'] = [
                'Monitor EEG patterns',
                'Maintain medication schedule',
            ];
        }

        return $analysis;
    }

    /**
     * Get live device data for dashboard
     */
    public function getLiveDeviceData(User $user, $limit = 50)
    {
        $vitalSigns = VitalSign::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        return [
            'vital_signs' => $vitalSigns,
            'current_analysis' => Cache::get("user_{$user->id}_analysis", []),
            'latest_seizure' => Seizure::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first(),
        ];
    }

    /**
     * Handle device disconnection and reconnection
     */
    public function handleDeviceDisconnection(User $user, $deviceType, $deviceId)
    {
        Log::warning("Device disconnected: {$deviceType} ({$deviceId}) for user {$user->id}");

        // Return disconnection info for queued jobs to broadcast
        Cache::put("device_reconnect_{$deviceId}", true, now()->addMinutes(5));

        return [
            'device_type' => $deviceType,
            'device_id' => $deviceId,
        ];
    }

    /**
     * Get device connection status
     */
    public function getDeviceStatus(User $user)
    {
        return [
            'polar' => Cache::get("device_polar_{$user->id}", false),
            'emotiv' => Cache::get("device_emotiv_{$user->id}", false),
            'esp32' => Cache::get("device_esp32_{$user->id}", false),
        ];
    }
}
