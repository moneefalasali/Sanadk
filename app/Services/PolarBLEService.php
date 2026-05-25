<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PolarBLEService
{
    /**
     * Initialize Polar BLE connection
     * This will be called from JavaScript/Capacitor
     */
    public static function initializeBLE()
    {
        return [
            'service_uuid' => '180d',  // Heart Rate Service
            'characteristic_uuid' => '2a37',  // Heart Rate Measurement
            'battery_service_uuid' => '180f',
            'battery_level_uuid' => '2a19',
        ];
    }

    /**
     * Parse Polar Heart Rate data from BLE
     */
    public static function parseHeartRateData($data)
    {
        try {
            // Heart Rate data format from Polar devices
            $flags = ord($data[0]);
            $offset = 1;

            // Check if heart rate is 8-bit or 16-bit
            $hrFormat = ($flags & 0x01) ? 16 : 8;
            
            if ($hrFormat === 16) {
                $heartRate = (ord($data[$offset + 1]) << 8) | ord($data[$offset]);
                $offset += 2;
            } else {
                $heartRate = ord($data[$offset]);
                $offset += 1;
            }

            // Parse RR intervals if available
            $rrIntervals = [];
            if ($flags & 0x10) {
                while ($offset < strlen($data)) {
                    $rrInterval = (ord($data[$offset + 1]) << 8) | ord($data[$offset]);
                    $rrIntervals[] = $rrInterval / 1024; // Convert to seconds
                    $offset += 2;
                }
            }

            return [
                'heart_rate' => $heartRate,
                'rr_intervals' => $rrIntervals,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('Error parsing Polar data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate HRV (Heart Rate Variability) from RR intervals
     */
    public static function calculateHRV($rrIntervals)
    {
        if (count($rrIntervals) < 2) {
            return 0;
        }

        // Calculate SDNN (Standard Deviation of NN intervals)
        $mean = array_sum($rrIntervals) / count($rrIntervals);
        $squareDiffs = array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $rrIntervals);
        $variance = array_sum($squareDiffs) / count($squareDiffs);
        $sdnn = sqrt($variance);

        // Calculate RMSSD (Root Mean Square of Successive Differences)
        $successiveDiffs = [];
        for ($i = 1; $i < count($rrIntervals); $i++) {
            $successiveDiffs[] = abs($rrIntervals[$i] - $rrIntervals[$i - 1]);
        }
        $meanSquare = array_sum(array_map(function ($x) {
            return $x * $x;
        }, $successiveDiffs)) / count($successiveDiffs);
        $rmssd = sqrt($meanSquare);

        return [
            'sdnn' => round($sdnn, 2),
            'rmssd' => round($rmssd, 2),
            'mean_rr' => round($mean, 2),
        ];
    }

    /**
     * Get available Polar devices
     */
    public static function getAvailableDevices()
    {
        // This would be implemented in JavaScript/Capacitor
        // Returns list of available Polar devices
        return Cache::get('available_polar_devices', []);
    }

    /**
     * Connect to specific Polar device
     */
    public static function connectToDevice($deviceId)
    {
        try {
            Cache::put("polar_device_{$deviceId}_connected", true, now()->addHours(8));
            Log::info("Connected to Polar device: {$deviceId}");
            
            return [
                'success' => true,
                'device_id' => $deviceId,
                'message' => 'Connected to Polar device',
            ];
        } catch (\Exception $e) {
            Log::error("Error connecting to Polar device: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to connect to device',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Disconnect from Polar device
     */
    public static function disconnectDevice($deviceId)
    {
        try {
            Cache::forget("polar_device_{$deviceId}_connected");
            Log::info("Disconnected from Polar device: {$deviceId}");
            
            return [
                'success' => true,
                'device_id' => $deviceId,
                'message' => 'Disconnected from Polar device',
            ];
        } catch (\Exception $e) {
            Log::error("Error disconnecting from Polar device: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to disconnect from device',
                'error' => $e->getMessage(),
            ];
        }
    }
}
