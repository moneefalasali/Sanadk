<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EmotivEEGService
{
    protected $cortexUrl = 'wss://api.emotivcloud.com';
    protected $clientId = 'DE83RyH24oCWgBq2HbC0M7ZyP1BPX9l5goUO5jCV';
    protected $clientSecret = '';
    protected $appId = 'com.ghala_tech.University-Project';

    /**
     * Initialize Emotiv EEG connection
     */
    public function initializeConnection()
    {
        return [
            'cortex_url' => $this->cortexUrl,
            'app_id' => $this->appId,
            'client_id' => $this->clientId,
            'message' => 'Emotiv EEG initialization data',
        ];
    }

    /**
     * Authenticate with Emotiv Cortex API
     */
    public function authenticate($accessToken = null)
    {
        try {
            // Check if we have cached token
            $token = Cache::get('emotiv_access_token');
            
            if ($token) {
                return [
                    'success' => true,
                    'access_token' => $token,
                    'message' => 'Using cached token',
                ];
            }

            // In production, implement proper OAuth2 flow
            // This is a placeholder for the authentication process
            Log::info('Emotiv authentication initiated');

            return [
                'success' => true,
                'message' => 'Authentication process initiated',
                'cortex_url' => $this->cortexUrl,
            ];
        } catch (\Exception $e) {
            Log::error('Emotiv authentication error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Authentication failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create EEG session
     */
    public function createSession($headsetId)
    {
        try {
            $sessionId = 'session_' . uniqid();
            
            Cache::put("emotiv_session_{$sessionId}", [
                'headset_id' => $headsetId,
                'created_at' => now(),
                'status' => 'active',
            ], now()->addHours(8));

            Log::info("EEG session created: {$sessionId}");

            return [
                'success' => true,
                'session_id' => $sessionId,
                'headset_id' => $headsetId,
                'message' => 'EEG session created',
            ];
        } catch (\Exception $e) {
            Log::error('EEG session creation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create session',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process raw EEG data from Emotiv
     */
    public function processRawEEGData($sessionId, $data)
    {
        try {
            // Extract channel data (10 channels for Emotiv EPOC)
            $channels = [
                'AF3' => $data['AF3'] ?? 0,
                'AF4' => $data['AF4'] ?? 0,
                'F3' => $data['F3'] ?? 0,
                'F4' => $data['F4'] ?? 0,
                'FC5' => $data['FC5'] ?? 0,
                'FC6' => $data['FC6'] ?? 0,
                'P7' => $data['P7'] ?? 0,
                'P8' => $data['P8'] ?? 0,
                'O1' => $data['O1'] ?? 0,
                'O2' => $data['O2'] ?? 0,
            ];

            // Calculate signal quality
            $signalQuality = $this->calculateSignalQuality($channels);

            // Detect artifacts
            $artifacts = $this->detectArtifacts($channels);

            return [
                'channels' => $channels,
                'signal_quality' => $signalQuality,
                'artifacts' => $artifacts,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('EEG data processing error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate signal quality for each channel
     */
    protected function calculateSignalQuality($channels)
    {
        $quality = [];
        
        foreach ($channels as $channel => $value) {
            // Normalize signal (0-100 scale)
            // Lower absolute values = better signal quality
            $absValue = abs($value);
            
            if ($absValue < 20) {
                $quality[$channel] = 100; // Excellent
            } elseif ($absValue < 50) {
                $quality[$channel] = 80; // Good
            } elseif ($absValue < 100) {
                $quality[$channel] = 60; // Fair
            } else {
                $quality[$channel] = 40; // Poor
            }
        }

        return $quality;
    }

    /**
     * Detect artifacts in EEG signal
     */
    protected function detectArtifacts($channels)
    {
        $artifacts = [];

        foreach ($channels as $channel => $value) {
            $absValue = abs($value);
            
            // Detect eye movement artifact (typically in AF3, AF4)
            if (in_array($channel, ['AF3', 'AF4']) && $absValue > 150) {
                $artifacts[] = [
                    'type' => 'eye_movement',
                    'channel' => $channel,
                    'severity' => 'high',
                ];
            }
            
            // Detect muscle artifact (typically in FC5, FC6)
            if (in_array($channel, ['FC5', 'FC6']) && $absValue > 200) {
                $artifacts[] = [
                    'type' => 'muscle_artifact',
                    'channel' => $channel,
                    'severity' => 'high',
                ];
            }
            
            // Detect electrode contact issues
            if ($absValue > 300) {
                $artifacts[] = [
                    'type' => 'electrode_contact',
                    'channel' => $channel,
                    'severity' => 'critical',
                ];
            }
        }

        return $artifacts;
    }

    /**
     * Detect seizure patterns in EEG
     */
    public function detectSeizurePatterns($channels, $history = [])
    {
        $seizureIndicators = [];

        // Check for high-amplitude rhythmic activity (typical seizure pattern)
        $avgAmplitude = array_sum(array_map('abs', $channels)) / count($channels);
        
        if ($avgAmplitude > 100) {
            $seizureIndicators[] = 'high_amplitude_activity';
        }

        // Check for synchronized activity across channels (spike-wave patterns)
        $correlations = $this->calculateChannelCorrelations($channels);
        if (max($correlations) > 0.8) {
            $seizureIndicators[] = 'synchronized_activity';
        }

        // Check for frequency changes
        if (count($history) > 0) {
            $frequencyChange = $this->detectFrequencyChange($channels, $history);
            if ($frequencyChange > 0.5) {
                $seizureIndicators[] = 'frequency_change';
            }
        }

        return [
            'indicators' => $seizureIndicators,
            'risk_level' => count($seizureIndicators) > 1 ? 'high' : 'normal',
            'confidence' => min(count($seizureIndicators) * 0.3, 1.0),
        ];
    }

    /**
     * Calculate correlations between channels
     */
    protected function calculateChannelCorrelations($channels)
    {
        $values = array_values($channels);
        $correlations = [];

        for ($i = 0; $i < count($values) - 1; $i++) {
            for ($j = $i + 1; $j < count($values); $j++) {
                // Simple correlation calculation
                $corr = abs($values[$i] * $values[$j]) / (abs($values[$i]) + abs($values[$j]) + 1);
                $correlations[] = $corr;
            }
        }

        return $correlations ?: [0];
    }

    /**
     * Detect frequency changes in EEG
     */
    protected function detectFrequencyChange($channels, $history)
    {
        if (empty($history)) {
            return 0;
        }

        $currentAvg = array_sum(array_map('abs', $channels)) / count($channels);
        $historyAvg = array_sum(array_map('abs', $history)) / count($history);

        $change = abs($currentAvg - $historyAvg) / ($historyAvg + 1);
        return min($change, 1.0);
    }

    /**
     * End EEG session
     */
    public function endSession($sessionId)
    {
        try {
            Cache::forget("emotiv_session_{$sessionId}");
            Log::info("EEG session ended: {$sessionId}");

            return [
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'EEG session ended',
            ];
        } catch (\Exception $e) {
            Log::error('EEG session end error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to end session',
                'error' => $e->getMessage(),
            ];
        }
    }
}
