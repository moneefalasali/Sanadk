<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MedicalDeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeviceDataController extends Controller
{
    protected $deviceService;

    public function __construct(MedicalDeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    /**
     * Receive Polar device data
     */
    public function receivePolarData(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validated = $request->validate([
                'heart_rate' => 'nullable|integer|min:30|max:200',
                'rr_interval' => 'nullable|numeric',
                'hrv' => 'nullable|numeric',
                'device_id' => 'required|string',
                'battery_level' => 'nullable|integer|min:0|max:100',
                'signal_quality' => 'nullable|integer|min:0|max:100',
            ]);

            // Dispatch processing to queue to ensure broadcasts go through queue
            \App\Jobs\ProcessHeartRateJob::dispatch($user->id, $validated)->onQueue('device-processing');

            return response()->json([
                'success' => true,
                'message' => 'Polar data queued for processing',
            ], 202);
        } catch (\Exception $e) {
            Log::error('Polar data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing Polar data',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Receive Emotiv EEG data
     */
    public function receiveEmotivData(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'AF3' => 'nullable|numeric',
                'AF4' => 'nullable|numeric',
                'F3' => 'nullable|numeric',
                'F4' => 'nullable|numeric',
                'FC5' => 'nullable|numeric',
                'FC6' => 'nullable|numeric',
                'P7' => 'nullable|numeric',
                'P8' => 'nullable|numeric',
                'O1' => 'nullable|numeric',
                'O2' => 'nullable|numeric',
                'session_id' => 'required|string',
            ]);

            \App\Jobs\ProcessEEGJob::dispatch($user->id, $validated)->onQueue('device-processing');

            return response()->json([
                'success' => true,
                'message' => 'EEG data queued for processing',
            ], 202);
        } catch (\Exception $e) {
            Log::error('Emotiv EEG error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing EEG data',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Receive ESP32 IoT sensor data
     */
    public function receiveESP32Data(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'heart_rate' => 'nullable|integer|min:30|max:200',
                'oxygen_level' => 'nullable|numeric|min:0|max:100',
                'temperature' => 'nullable|numeric|min:30|max:45',
                'bp_systolic' => 'nullable|integer|min:60|max:200',
                'bp_diastolic' => 'nullable|integer|min:40|max:130',
                'device_id' => 'required|string',
                'signal_quality' => 'nullable|integer|min:0|max:100',
            ]);

            \App\Jobs\ProcessDeviceDataJob::dispatch($user->id, 'esp32', $validated)->onQueue('device-processing');

            return response()->json([
                'success' => true,
                'message' => 'ESP32 data queued for processing',
            ], 202);
        } catch (\Exception $e) {
            Log::error('ESP32 data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing ESP32 data',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get live device data for dashboard
     */
    public function getLiveData(Request $request)
    {
        try {
            $user = Auth::user();
            $limit = $request->input('limit', 50);

            $data = $this->deviceService->getLiveDeviceData($user, $limit);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Get live data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving live data',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get device connection status
     */
    public function getDeviceStatus(Request $request)
    {
        try {
            $user = Auth::user();
            $status = $this->deviceService->getDeviceStatus($user);

            return response()->json([
                'success' => true,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Get device status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving device status',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Handle device disconnection
     */
    public function handleDeviceDisconnection(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'device_type' => 'required|string|in:polar,emotiv,esp32',
                'device_id' => 'required|string',
            ]);

            \App\Jobs\ProcessDeviceDisconnectionJob::dispatch($user->id, $validated['device_type'], $validated['device_id'])->onQueue('device-processing');

            return response()->json([
                'success' => true,
                'message' => 'Device disconnection queued for processing',
            ], 202);
        } catch (\Exception $e) {
            Log::error('Device disconnection error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error handling device disconnection',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
