<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/risk-check', [App\Http\Controllers\DashboardController::class, 'riskCheck'])->name('risk-check');
    Route::get('/data-entry', [App\Http\Controllers\DashboardController::class, 'dataEntry'])->name('data-entry');
    Route::post('/data-entry', [App\Http\Controllers\DashboardController::class, 'storeDataEntry'])->name('data-entry.store');
    Route::get('/seizures', [App\Http\Controllers\DashboardController::class, 'seizures'])->name('seizures');
    Route::get('/reports', [App\Http\Controllers\DashboardController::class, 'reports'])->name('reports');
    Route::get('/devices', [App\Http\Controllers\DashboardController::class, 'devices'])->name('devices');
    Route::post('/devices', [App\Http\Controllers\DashboardController::class, 'storeDevice'])->name('devices.store');
    Route::post('/devices/simulate', [App\Http\Controllers\DashboardController::class, 'simulateDevices'])->name('devices.simulate');
    Route::post('/devices/stop-simulation', [App\Http\Controllers\DashboardController::class, 'stopSimulation'])->name('devices.stop-simulation');
    Route::post('/devices/toggle-simulation', [App\Http\Controllers\DashboardController::class, 'toggleSimulation'])->name('devices.toggle-simulation');
    Route::post('/devices/analyze', [App\Http\Controllers\DashboardController::class, 'analyzeDevices'])->name('devices.analyze');
    Route::get('/devices/{device}/data', [App\Http\Controllers\DashboardController::class, 'getDeviceData'])->name('devices.data');
    Route::post('/devices/{device}/bluetooth-data', [App\Http\Controllers\DashboardController::class, 'receiveDeviceDataFromBrowser'])->name('devices.bluetooth-data');
    Route::get('/devices/polar/connect', [App\Http\Controllers\DashboardController::class, 'connectPolar'])->name('devices.polar.connect');
    Route::get('/devices/polar/callback', [App\Http\Controllers\DashboardController::class, 'polarCallback'])->name('devices.polar.callback');
    Route::get('/devices/live-data', [App\Http\Controllers\DashboardController::class, 'getLiveDeviceData'])->name('devices.live-data');
    Route::get('/notifications', [App\Http\Controllers\DashboardController::class, 'notifications'])->name('notifications');
    Route::get('/map', [App\Http\Controllers\DashboardController::class, 'map'])->name('map');
    Route::get('/family', [App\Http\Controllers\DashboardController::class, 'family'])->name('family');
    Route::post('/family', [App\Http\Controllers\DashboardController::class, 'storeFamilyRequest'])->name('family.store');
    Route::post('/family/accept-request', [App\Http\Controllers\DashboardController::class, 'acceptFamilyRequest'])->name('family.accept-request');
    Route::post('/family/reject-request', [App\Http\Controllers\DashboardController::class, 'rejectFamilyRequest'])->name('family.reject-request');
    Route::get('/doctor', [App\Http\Controllers\DashboardController::class, 'doctor'])->name('doctor');
    Route::get('/doctor/monitor/{patient?}', [App\Http\Controllers\DashboardController::class, 'doctorMonitor'])->name('doctor.monitor');
    Route::get('/doctor/reports', [App\Http\Controllers\DashboardController::class, 'doctorReports'])->name('doctor.reports');
    Route::post('/doctor', [App\Http\Controllers\DashboardController::class, 'storeDoctorRequest'])->name('doctor.store');
    Route::post('/doctor/patient/{patient}/note', [App\Http\Controllers\DashboardController::class, 'addPatientNote'])->name('doctor.patient.note');
    Route::post('/doctor/patient/{patient}/notify', [App\Http\Controllers\DashboardController::class, 'sendPatientNotification'])->name('doctor.patient.notify');
    Route::get('/patient/{patient}/details', [App\Http\Controllers\DashboardController::class, 'patientDetails'])->name('patient.details');
    // Debug: emit a test MedicalDataUpdated event for E2E verification (auth only)
    Route::post('/test/patient/{patient}/emit', function (\App\Models\Patient $patient) {
        $payload = [
            'vital_sign' => [
                'heart_rate' => rand(60,120),
                'oxygen_level' => rand(94,100),
                'temperature' => round(36 + (rand(0,20)/10),1),
                'respiratory_rate' => rand(12,24),
                'blood_pressure' => rand(110,130) . '/' . rand(70,90),
            ],
            'devices' => [
                ['name' => 'Polar H10', 'type' => 'ecg', 'status' => 'connected'],
                ['name' => 'Emotiv EPOC+', 'type' => 'eeg', 'status' => 'connected'],
            ],
            'eeg_status' => 'مستقر',
            'eeg_wave' => ['AF3' => rand(5,20), 'AF4' => rand(5,20), 'F3' => rand(5,20), 'F4' => rand(5,20)],
            'analysis' => ['risk_score' => rand(5,40)/100, 'alert_level' => 'stable'],
            'connection_status' => 'connected',
        ];
        event(new App\Events\MedicalDataUpdated($patient->id, $payload));
        return response()->json(['ok' => true, 'patient' => $patient->id]);
    })->name('test.patient.emit');
    Route::get('/settings', [App\Http\Controllers\DashboardController::class, 'settings'])->name('settings');
    Route::get('/ai-analysis', [App\Http\Controllers\DashboardController::class, 'getAIAnalysis'])->name('ai.analysis');
    Route::get('/ai-chat', function() { return view('dashboards.ai_chat'); })->name('ai-chat');
    Route::post('/emergency/trigger', [App\Http\Controllers\EmergencyController::class, 'trigger'])->name('emergency.trigger');
    Route::post('/emergency/safe/{id}', [App\Http\Controllers\EmergencyController::class, 'iAmSafe'])->name('emergency.safe');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
