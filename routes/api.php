<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/vitals/update', [ApiController::class, 'updateVitals']);
Route::get('/patient/{id}/status', [ApiController::class, 'getPatientStatus']);
Route::post('/search-hospitals-ai', [ApiController::class, 'searchHospitalsAI']);
Route::get('/hospitals/nearby', [ApiController::class, 'getNearbyHospitals']);

// Medical Device Integration APIs
Route::middleware('auth:sanctum')->group(function () {
    // Polar Device Data
    Route::post('/devices/polar/data', [\App\Http\Controllers\Api\DeviceDataController::class, 'receivePolarData']);

    // Emotiv EEG Data
    Route::post('/devices/emotiv/data', [\App\Http\Controllers\Api\DeviceDataController::class, 'receiveEmotivData']);

    // ESP32 IoT Sensor Data
    Route::post('/devices/esp32/data', [\App\Http\Controllers\Api\DeviceDataController::class, 'receiveESP32Data']);

    // Live Device Data
    Route::get('/devices/live-data', [\App\Http\Controllers\Api\DeviceDataController::class, 'getLiveData']);

    // Device Status
    Route::get('/devices/status', [\App\Http\Controllers\Api\DeviceDataController::class, 'getDeviceStatus']);

    // Device Disconnection
    Route::post('/devices/disconnection', [\App\Http\Controllers\Api\DeviceDataController::class, 'handleDeviceDisconnection']);

    // Medical Reports
    Route::post('/reports/session', [\App\Http\Controllers\Api\MedicalReportController::class, 'generateSessionReport']);
    Route::post('/reports/daily', [\App\Http\Controllers\Api\MedicalReportController::class, 'generateDailyReport']);
    Route::post('/reports/weekly', [\App\Http\Controllers\Api\MedicalReportController::class, 'generateWeeklyReport']);
    Route::get('/reports', [\App\Http\Controllers\Api\MedicalReportController::class, 'getUserReports']);
    Route::get('/reports/{reportId}', [\App\Http\Controllers\Api\MedicalReportController::class, 'getReport']);
    Route::get('/reports/{reportId}/csv', [\App\Http\Controllers\Api\MedicalReportController::class, 'exportAsCSV']);
    Route::get('/reports/{reportId}/pdf', [\App\Http\Controllers\Api\MedicalReportController::class, 'downloadPDF']);
    Route::delete('/reports/{reportId}', [\App\Http\Controllers\Api\MedicalReportController::class, 'deleteReport']);
});

// Device bridge: receive device data from local DeviceManager bridge
Route::post('/devices/{device}/data', [\App\Http\Controllers\DashboardController::class, 'receiveDeviceData']);
