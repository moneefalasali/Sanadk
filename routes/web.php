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
    Route::get('/devices/live-data', [App\Http\Controllers\DashboardController::class, 'getLiveDeviceData'])->name('devices.live-data');
    Route::get('/notifications', [App\Http\Controllers\DashboardController::class, 'notifications'])->name('notifications');
    Route::get('/map', [App\Http\Controllers\DashboardController::class, 'map'])->name('map');
    Route::get('/family', [App\Http\Controllers\DashboardController::class, 'family'])->name('family');
    Route::post('/family', [App\Http\Controllers\DashboardController::class, 'storeFamilyRequest'])->name('family.store');
    Route::post('/family/accept-request', [App\Http\Controllers\DashboardController::class, 'acceptFamilyRequest'])->name('family.accept-request');
    Route::post('/family/reject-request', [App\Http\Controllers\DashboardController::class, 'rejectFamilyRequest'])->name('family.reject-request');
    Route::get('/doctor', [App\Http\Controllers\DashboardController::class, 'doctor'])->name('doctor');
    Route::post('/doctor', [App\Http\Controllers\DashboardController::class, 'storeDoctorRequest'])->name('doctor.store');
    Route::post('/doctor/patient/{patient}/note', [App\Http\Controllers\DashboardController::class, 'addPatientNote'])->name('doctor.patient.note');
    Route::post('/doctor/patient/{patient}/notify', [App\Http\Controllers\DashboardController::class, 'sendPatientNotification'])->name('doctor.patient.notify');
    Route::get('/patient/{patient}/details', [App\Http\Controllers\DashboardController::class, 'patientDetails'])->name('patient.details');
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
