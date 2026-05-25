<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Seizure;
use App\Models\VitalSign;
use App\Models\User;
use App\Models\Device;
use App\Models\DailyEntry;
use App\Models\AppNotification;
use App\Models\EmergencyContact;
use App\Models\PatientDoctor;
use App\Services\PolarAccessLinkService;
use App\Services\SeizurePrediction;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $totalPatients = User::where('role', 'patient')->count();
            $totalDoctors = User::where('role', 'doctor')->count();
            $totalSeizures = Seizure::count();
            $todaySeizures = Seizure::whereDate('created_at', today())->count();
            $patients = User::where('role', 'patient')->with('seizures')->get();
            $doctors = User::where('role', 'doctor')->with('patients')->get();
            $latestSeizures = Seizure::latest()->take(20)->get();
            $predictedRate = $totalSeizures > 0 ? round(Seizure::where('is_predicted', true)->count() / $totalSeizures * 100, 1) : 0;
            $durations = Seizure::whereNotNull('end_time')->get()->map(function ($seizure) {
                return $seizure->end_time->diffInMinutes($seizure->start_time);
            })->filter()->all();
            $averageDuration = count($durations) ? round(array_sum($durations) / count($durations), 1) : 0;

            return view('dashboards.admin', compact(
                'totalPatients',
                'totalDoctors',
                'totalSeizures',
                'todaySeizures',
                'patients',
                'doctors',
                'latestSeizures',
                'predictedRate',
                'averageDuration'
            ));
        } elseif ($user->role === 'doctor') {
            $patients = $user->patients()->with(['vitalSigns', 'seizures'])->get();
            $activePatientCount = $patients->filter(function ($patient) {
                return $patient->seizures->whereNull('end_time')->count() > 0;
            })->count();
            $totalSeizures = $patients->sum(function ($patient) {
                return $patient->seizures->count();
            });
            $predictionRate = $totalSeizures > 0 ? round($patients->sum(function ($patient) {
                return $patient->seizures->where('is_predicted', true)->count();
            }) / $totalSeizures * 100, 1) : 0;
            $durations = $patients->flatMap(function ($patient) {
                return $patient->seizures->whereNotNull('end_time')->map(function ($seizure) {
                    return $seizure->end_time->diffInMinutes($seizure->start_time);
                });
            })->filter()->all();
            $avgSeizureDuration = count($durations) ? round(array_sum($durations) / count($durations), 1) : 0;
            return view('dashboards.doctor', compact('patients', 'activePatientCount', 'totalSeizures', 'predictionRate', 'avgSeizureDuration'));
        } elseif ($user->role === 'family') {
            $familyPatients = $user->emergencyContacts()->where('status', 'accepted')->with('user.seizures', 'user.vitalSigns')->get()
                ->pluck('user')
                ->filter()
                ->map(function ($patient) {
                    $latestSeizure = $patient->seizures->sortByDesc('start_time')->first();
                    $latestVital = $patient->vitalSigns->sortByDesc('created_at')->first();

                    return [
                        'id' => $patient->id,
                        'name' => $patient->name,
                        'email' => $patient->email,
                        'phone' => $patient->phone,
                        'address' => $patient->address,
                        'latitude' => $latestSeizure?->latitude,
                        'longitude' => $latestSeizure?->longitude,
                        'status' => $patient->seizures->whereNull('end_time')->count() ? 'alert' : 'stable',
                        'last_update' => $latestVital?->created_at->diffForHumans() ?? 'لا يوجد',
                        'active_seizures' => $patient->seizures->whereNull('end_time')->count(),
                        'seizures' => $patient->seizures,
                    ];
                });

            // Get pending requests for family members
            $pendingRequests = \App\Models\EmergencyContact::where('contact_user_id', $user->id)
                ->where('status', 'pending')
                ->with('user')
                ->get()
                ->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'name' => $contact->user->name,
                        'email' => $contact->user->email,
                        'phone' => $contact->phone,
                        'relationship' => $contact->relationship,
                    ];
                });

            // Get notifications for family member (related to their patients)
            $patientIds = $familyPatients->pluck('id');
            $notifications = AppNotification::whereIn('user_id', $patientIds)
                ->latest()
                ->take(20)
                ->get()
                ->map(function ($notification) use ($familyPatients) {
                    $patient = $familyPatients->firstWhere('id', $notification->user_id);
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => $notification->type,
                        'patient_name' => $patient ? $patient['name'] : 'غير معروف',
                        'created_at' => $notification->created_at,
                        'is_read' => $notification->is_read,
                    ];
                });

            return view('dashboards.family', [
                'patients' => $familyPatients,
                'pendingRequests' => $pendingRequests,
                'notifications' => $notifications,
                'contacts' => collect(), // Empty collection for family users
            ]);
        }

        $latestVitals = VitalSign::where('user_id', $user->id)->latest()->first();
        $recentSeizures = Seizure::where('user_id', $user->id)->latest()->take(5)->get();
        $lastSeizure = Seizure::where('user_id', $user->id)->whereNotNull('end_time')->latest()->first();
        $latestEntry = DailyEntry::where('user_id', $user->id)->latest()->first();
        $devices = Device::where('user_id', $user->id)->get();
        $connectedDevices = $devices->where('status', 'connected')->count();
        $deviceTypes = $devices->groupBy('type')->count();
        $linkedDoctors = $user->doctors()->count();
        $familyContacts = $user->emergencyContacts()->count();

        // Calculate dynamic risk score
        $riskScore = $this->calculateRiskScore($user->id);

        // Calculate sleep quality from latest entry
        $sleepQuality = $latestEntry ? $this->getSleepQualityText($latestEntry->sleep_quality) : 'غير محدد';

        // Calculate stress level from latest entry
        $stressLevel = $latestEntry ? $this->getStressLevelText($latestEntry->stress_level) : 'غير محدد';

        // Calculate activity level from latest entry
        $activityLevel = $latestEntry ? $this->getActivityLevelText($latestEntry->activity_level) : 'غير محدد';

        // Get unread notifications count
        $unreadNotifications = AppNotification::where('user_id', $user->id)->where('is_read', false)->count();

        // Get live device data
        $liveDeviceData = $this->getLiveDeviceDataArray($user->id);

        return view('dashboards.patient', [
            'latestVitals' => $latestVitals,
            'recentSeizures' => $recentSeizures,
            'lastSeizure' => $lastSeizure,
            'latestEntry' => $latestEntry,
            'riskScore' => $riskScore,
            'sleepQuality' => $sleepQuality,
            'stressLevel' => $stressLevel,
            'activityLevel' => $activityLevel,
            'unreadNotifications' => $unreadNotifications,
            'connectedDevices' => $connectedDevices,
            'deviceTypes' => $deviceTypes,
            'linkedDoctors' => $linkedDoctors,
            'familyContacts' => $familyContacts,
            'liveDeviceData' => $liveDeviceData,
        ]);
    }

    public function riskCheck()
    {
        $user = Auth::user();

        // Get live device data
        $liveDeviceData = $this->getLiveDeviceDataArray($user->id);

        // Use AI service for analysis
        $predictionService = app(SeizurePrediction::class);
        $devices = Device::where('user_id', $user->id)->where('status', 'connected')->get();
        $deviceData = [];

        foreach ($devices as $device) {
            $latestData = $device->getLastDataAttribute();
            if ($latestData) {
                $deviceData[$device->type] = $latestData;
            }
        }

        $analysis = $predictionService->analyzeDeviceData($user, $deviceData);

        $latestVitals = VitalSign::where('user_id', $user->id)->latest()->first();
        $latestEntry = DailyEntry::where('user_id', $user->id)->latest()->first();
        $connectedDevices = Device::where('user_id', $user->id)->where('status', 'connected')->count();
        $deviceTypes = Device::where('user_id', $user->id)->get()->groupBy('type')->count();
        $linkedDoctors = $user->doctors()->count();
        $familyContacts = $user->emergencyContacts()->count();
        $lastSeizure = Seizure::where('user_id', $user->id)->whereNotNull('end_time')->latest()->first();

        // Use dynamic risk score from analysis
        $riskScore = round($analysis['probability'] * 100);
        $riskLabel = $analysis['risk_level'];
        $timeToEvent = $analysis['time_to_event'];

        $sleepQuality = $latestEntry ? $this->getSleepQualityText($latestEntry->sleep_quality) : 'غير محدد';
        $stressLevel = $latestEntry ? $this->getStressLevelText($latestEntry->stress_level) : 'غير محدد';
        $activityLevel = $latestEntry ? $this->getActivityLevelText($latestEntry->activity_level) : 'غير محدد';

        return view('dashboards.risk_check', compact(
            'riskScore',
            'riskLabel',
            'timeToEvent',
            'analysis',
            'liveDeviceData',
            'latestVitals',
            'latestEntry',
            'lastSeizure',
            'connectedDevices',
            'deviceTypes',
            'linkedDoctors',
            'familyContacts',
            'sleepQuality',
            'stressLevel',
            'activityLevel'
        ));
    }

    public function dataEntry()
    {
        $user = Auth::user();
        $todayEntry = DailyEntry::where('user_id', $user->id)->whereDate('entry_date', today())->first();
        $previousEntries = DailyEntry::where('user_id', $user->id)->latest()->take(5)->get();

        return view('dashboards.data_entry', compact('todayEntry', 'previousEntries'));
    }

    public function storeDataEntry(Request $request)
    {
        $data = $request->validate([
            'sleep_quality' => 'required|integer|min:1|max:5',
            'stress_level' => 'required|integer|min:1|max:5',
            'medication_taken' => 'required|boolean',
            'activity_level' => 'required|string|max:50',
        ]);

        $data['user_id'] = Auth::id();
        $data['entry_date'] = today();
        $data['medication_taken'] = (bool) $request->input('medication_taken');

        DailyEntry::updateOrCreate([
            'user_id' => Auth::id(),
            'entry_date' => today(),
        ], $data);

        return redirect()->back()->with('success', 'تم حفظ البيانات بنجاح');
    }

    public function seizures()
    {
        $user = Auth::user();
        $seizures = Seizure::where('user_id', $user->id)->latest()->get();
        $riskScore = $this->calculateRiskScore($user->id);
        return view('dashboards.seizures', compact('seizures', 'riskScore'));
    }

    public function reports()
    {
        $user = Auth::user();
        $seizures = Seizure::where('user_id', $user->id)->latest()->get();
        $averageDuration = $seizures->whereNotNull('end_time')->map(fn($s) => $s->end_time->diffInMinutes($s->start_time))->average() ?? 0;
        $predictionRate = $seizures->count() ? round($seizures->where('is_predicted', true)->count() / $seizures->count() * 100, 1) : 0;
        $riskScore = $this->calculateRiskScore($user->id);

        $latestEntry = DailyEntry::where('user_id', $user->id)->latest()->first();
        $sleepImpact = $latestEntry ? max(10, min(45, (6 - $latestEntry->sleep_quality) * 8)) : 25;
        $stressImpact = $latestEntry ? min(50, $latestEntry->stress_level * 10) : 30;
        $activityImpact = $latestEntry ? ($latestEntry->activity_level === 'low' ? 35 : ($latestEntry->activity_level === 'medium' ? 20 : 15)) : 20;
        $otherImpact = max(5, 100 - ($sleepImpact + $stressImpact + $activityImpact));

        $grouped = $seizures->groupBy(fn($seizure) => $seizure->start_time->format('d/m'));
        $riskLabels = $grouped->keys()->take(5)->values()->all();
        $riskData = $grouped->map(fn($group) => $group->count())->take(5)->values()->all();

        if (empty($riskLabels)) {
            $riskLabels = collect(range(4, 0))->map(fn($days) => now()->subDays($days)->format('d/m'))->all();
            $riskData = array_fill(0, 5, 0);
        }

        return view('dashboards.reports', compact(
            'seizures',
            'averageDuration',
            'predictionRate',
            'riskScore',
            'sleepImpact',
            'stressImpact',
            'activityImpact',
            'otherImpact',
            'riskLabels',
            'riskData'
        ));
    }

    public function devices()
    {
        $user = Auth::user();
        $devices = Device::where('user_id', $user->id)->get();
        $connectedCount = $devices->where('status', 'connected')->count();
        $batteryAverage = $devices->avg('battery_level') ?? 0;
        $deviceTypes = $devices->groupBy('type')->map->count();
        $riskScore = $this->calculateRiskScore($user->id);

        return view('dashboards.devices', compact('devices', 'connectedCount', 'batteryAverage', 'deviceTypes', 'riskScore'));
    }

    public function storeDevice(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:eeg,ecg,emg',
        ]);

        Device::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'type' => $request->type,
            'status' => 'connected',
            'battery_level' => rand(70, 100),
        ]);

        return redirect()->route('devices')->with('success', 'تمت إضافة الجهاز بنجاح. يمكنك الآن مراقبة حالته مباشرة.');
    }

    public function simulateDevices()
    {
        $userId = Auth::id();
        $existing = Device::where('user_id', $userId)->count();

        if ($existing === 0) {
            Device::insert([
                ['user_id' => $userId, 'name' => 'EEG Emotiv EPOC X', 'type' => 'eeg', 'status' => 'connected', 'battery_level' => 94, 'simulation_mode' => true, 'created_at' => now(), 'updated_at' => now()],
                ['user_id' => $userId, 'name' => 'ECG Polar H10', 'type' => 'ecg', 'status' => 'connected', 'battery_level' => 88, 'simulation_mode' => true, 'created_at' => now(), 'updated_at' => now()],
                ['user_id' => $userId, 'name' => 'EMG ESP32 MyoWare', 'type' => 'emg', 'status' => 'connected', 'battery_level' => 76, 'simulation_mode' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        } else {
            // Enable simulation mode for existing devices
            Device::where('user_id', $userId)->update(['simulation_mode' => true]);
        }

        // Generate initial simulated data
        $devices = Device::where('user_id', $userId)->get();
        foreach ($devices as $device) {
            $device->generateSimulatedData();
        }

        return redirect()->route('devices')->with('success', 'تم تشغيل وضع المحاكاة للأجهزة. يمكنك مشاهدة حالة الأجهزة مباشرة.');
    }

    public function stopSimulation(Request $request)
    {
        $userId = Auth::id();
        Device::where('user_id', $userId)->update(['simulation_mode' => false]);

        return response()->json([
            'success' => true,
            'message' => 'تم إيقاف وضع المحاكاة لجميع الأجهزة.'
        ]);
    }

    public function toggleSimulation(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
        ]);

        $device = Device::where('id', $request->device_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $device->update(['simulation_mode' => !$device->simulation_mode]);

        if ($device->simulation_mode) {
            $device->generateSimulatedData();
        }

        return response()->json([
            'success' => true,
            'simulation_mode' => $device->simulation_mode,
            'message' => $device->simulation_mode ? 'تم تشغيل وضع المحاكاة' : 'تم إيقاف وضع المحاكاة'
        ]);
    }

    public function getDeviceData(Request $request)
    {
        // Support getting device id from route parameter or request body
        $deviceId = $request->route('device') ?? $request->input('device_id');

        if (! $deviceId) {
            return response()->json(["success" => false, "message" => 'لم يتم تحديد معرف الجهاز.'], 400);
        }

        $device = Device::where('id', $deviceId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $device) {
            return response()->json(["success" => false, "message" => 'لم يتم العثور على الجهاز أو ليس لديك صلاحية الوصول إليه.'], 404);
        }

        try {
            // Generate new simulated data if in simulation mode
            if ($device->simulation_mode) {
                $device->generateSimulatedData();
                $device->refresh();
            }

            $data = $device->getLastDataAttribute();

            return response()->json([
                'success' => true,
                'device' => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'type' => $device->type,
                    'status' => $device->status,
                    'battery_level' => $device->battery_level,
                    'simulation_mode' => $device->simulation_mode,
                    'last_updated' => $device->updated_at->diffForHumans()
                ],
                'data' => $data
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'حدث خطأ داخلي أثناء جلب بيانات الجهاز.'], 500);
        }
    }

    /**
     * Receive device data posted by an external bridge (DeviceManager).
     * Expected: POST /api/devices/{device}/data
     */
    public function receiveDeviceData(Request $request)
    {
        $incomingToken = $request->bearerToken();
        $expected = env('SANADK_INCOMING_TOKEN') ?: env('SANADK_API_TOKEN');

        if ($expected && $incomingToken !== $expected) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $deviceParam = $request->route('device');
        $payload = $request->all();

        // Find device by numeric id or by name
        $device = null;
        if (is_numeric($deviceParam)) {
            $device = Device::find($deviceParam);
        }
        if (!$device) {
            $device = Device::where('name', $deviceParam)->first();
        }

        // If not found, optionally create if user_id provided
        if (!$device) {
            if (!empty($payload['user_id'])) {
                $device = Device::create([
                    'user_id' => $payload['user_id'],
                    'name' => $deviceParam,
                    'type' => $payload['type'] ?? 'ecg',
                    'status' => 'connected',
                    'battery_level' => $payload['battery_level'] ?? null,
                    'simulation_mode' => false,
                ]);
            } else {
                return response()->json(['success' => false, 'message' => 'Device not found and no user_id provided'], 404);
            }
        }

        try {
            // Remove internal fields before storing
            $storeData = $payload;
            unset($storeData['user_id']);
            unset($storeData['device_id']);

            $device->update([
                'last_data' => is_array($storeData) ? json_encode($storeData) : $storeData,
                'status' => 'connected',
                'battery_level' => $payload['battery_level'] ?? $device->battery_level,
            ]);

            return response()->json(['success' => true, 'message' => 'Data stored']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Internal error'], 500);
        }
    }

    /**
     * Receive device data posted by browser Web Bluetooth connection (authenticated session).
     */
    public function receiveDeviceDataFromBrowser(Request $request, $device)
    {
        $user = Auth::user();

        // Find device owned by user
        $deviceModel = Device::where('user_id', $user->id)
            ->where(function($q) use ($device) {
                if (is_numeric($device)) {
                    $q->where('id', $device);
                } else {
                    $q->where('name', $device);
                }
            })->first();

        if (!$deviceModel) {
            return response()->json(['success' => false, 'message' => 'Device not found or not authorized'], 404);
        }

        $payload = $request->all();

        try {
            $storeData = $payload;
            $deviceModel->update([
                'last_data' => is_array($storeData) ? json_encode($storeData) : $storeData,
                'status' => 'connected'
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Internal error'], 500);
        }
    }

    public function connectPolar(Request $request, PolarAccessLinkService $service)
    {
        $state = bin2hex(random_bytes(16));
        session(['polar_oauth_state' => $state]);

        return redirect($service->authorizeUrl($state));
    }

    public function polarCallback(Request $request, PolarAccessLinkService $service)
    {
        if ($request->has('error')) {
            return redirect()->route('devices')->with('error', 'فشل الاتصال بـ Polar: ' . $request->input('error_description', $request->input('error')));
        }

        if ($request->input('state') !== session('polar_oauth_state')) {
            return redirect()->route('devices')->with('error', 'حالة المصادقة غير صحيحة. حاول مرة أخرى.');
        }

        $code = $request->input('code');

        try {
            $tokenData = $service->exchangeCode($code);
            $user = Auth::user();
            $user->update([
                'polar_owner_id' => $tokenData['owner_id'] ?? $tokenData['user_id'] ?? null,
                'polar_access_token' => $tokenData['access_token'] ?? null,
                'polar_refresh_token' => $tokenData['refresh_token'] ?? null,
                'polar_token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds($tokenData['expires_in']) : null,
            ]);

            return redirect()->route('devices')->with('success', 'تم ربط حساب Polar بنجاح. سيتم عرض البيانات عند تحديثها.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('devices')->with('error', 'حدث خطأ أثناء استكمال الربط مع Polar.');
        }
    }

    /**
     * Get live device data for dashboard display
     */
    public function getLiveDeviceData()
    {
        $userId = Auth::id();
        $devices = Device::where('user_id', $userId)->where('status', 'connected')->get();
        $liveData = [
            'success' => true,
            'heart_rate' => null,
            'blood_pressure_systolic' => null,
            'blood_pressure_diastolic' => null,
            'oxygen_level' => null,
            'temperature' => null,
            'muscle_tension' => null,
            'nerve_signals' => null,
            'brain_activity' => null,
            'last_updated' => null
        ];

        foreach ($devices as $device) {
            if ($device->simulation_mode) {
                $device->generateSimulatedData();
                $device->refresh();
            }

            $data = $device->getLastDataAttribute();
            if ($data) {
                // Map device data to the expected format
                if ($device->type === 'ecg') {
                    if (isset($data['heart_rate'])) $liveData['heart_rate'] = $data['heart_rate'];
                    if (isset($data['blood_pressure_systolic'])) $liveData['blood_pressure_systolic'] = $data['blood_pressure_systolic'];
                    if (isset($data['blood_pressure_diastolic'])) $liveData['blood_pressure_diastolic'] = $data['blood_pressure_diastolic'];
                    if (isset($data['oxygen_level'])) {
                        $liveData['oxygen_level'] = $data['oxygen_level'];
                    } elseif (isset($data['oxygen_saturation'])) {
                        $liveData['oxygen_level'] = $data['oxygen_saturation'];
                    }
                    if (isset($data['temperature'])) $liveData['temperature'] = $data['temperature'];
                } elseif ($device->type === 'emg') {
                    if (isset($data['tension'])) {
                        $liveData['muscle_tension'] = $data['tension'];
                    }
                    if (isset($data['nerve_signals'])) {
                        $liveData['nerve_signals'] = $data['nerve_signals'];
                    }
                } elseif ($device->type === 'eeg') {
                    if (isset($data['activity_level'])) {
                        $liveData['brain_activity'] = $data['activity_level'];
                    }
                    if (isset($data['alpha'])) {
                        $liveData['brain_alpha'] = $data['alpha'];
                    }
                    if (isset($data['beta'])) {
                        $liveData['brain_beta'] = $data['beta'];
                    }
                }

                if (!$liveData['last_updated'] || $device->updated_at > $liveData['last_updated']) {
                    $liveData['last_updated'] = $device->updated_at;
                }
            }
        }

        return response()->json($liveData)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function analyzeDevices(Request $request)
    {
        $user = Auth::user();
        $devices = Device::where('user_id', $user->id)->where('status', 'connected')->get();

        if ($devices->isEmpty()) {
            return response()->json([
                'error' => 'لا توجد أجهزة متصلة للتحليل. يرجى التأكد من وجود أجهزة متصلة.'
            ]);
        }

        // Use AI service for analysis
        $predictionService = app(SeizurePrediction::class);
        $deviceData = [];

        foreach ($devices as $device) {
            if ($device->simulation_mode) {
                $device->generateSimulatedData();
                $device->refresh();
            }

            $latestData = $device->getLastDataAttribute();
            if ($latestData) {
                $deviceData[$device->type] = $latestData;
            }
        }

        $analysis = $predictionService->analyzeDeviceData($user, $deviceData);

        // Get nearest hospitals (mock data for now)
        $nearestHospitals = [
            ['name' => 'مستشفى الملك فيصل', 'address' => 'الرياض، المملكة العربية السعودية', 'distance' => '2.5 كم', 'eta' => '8 دقائق'],
            ['name' => 'مستشفى الملك خالد', 'address' => 'الرياض، المملكة العربية السعودية', 'distance' => '4.1 كم', 'eta' => '12 دقيقة'],
            ['name' => 'مستشفى الرياض', 'address' => 'الرياض، المملكة العربية السعودية', 'distance' => '6.3 كم', 'eta' => '18 دقيقة'],
        ];

        return response()->json([
            'activity' => $analysis['activity'] ?? 'resting',
            'risk_level' => $analysis['risk_level'] ?? 'low',
            'recommendations' => $analysis['recommendations'] ?? [
                'تجنب الأنشطة الشاقة',
                'تأكد من تناول الأدوية بانتظام',
                'راقب مستويات التوتر',
                'احصل على قسط كافٍ من النوم'
            ],
            'nearest_hospitals' => $nearestHospitals
        ]);
    }

    /**
     * Get live device data array for view
     */
    private function getLiveDeviceDataArray($userId)
    {
        $devices = Device::where('user_id', $userId)->where('status', 'connected')->get();
        $liveData = [
            'eeg' => null,
            'ecg' => null,
            'emg' => null,
            'last_updated' => null
        ];

        foreach ($devices as $device) {
            if ($device->simulation_mode) {
                $device->generateSimulatedData();
                $device->refresh();
            }

            $data = $device->getLastDataAttribute();
            if ($data) {
                $liveData[$device->type] = $data;
                if (!$liveData['last_updated'] || $device->updated_at > $liveData['last_updated']) {
                    $liveData['last_updated'] = $device->updated_at;
                }
            }
        }

        return $liveData;
    }

    public function notifications()
    {
        $notifications = AppNotification::where('user_id', Auth::id())->latest()->get();
        return view('dashboards.notifications', compact('notifications'));
    }

    public function map()
    {
        $user = Auth::user();
        $patients = collect();

        if ($user->role === 'doctor') {
            // Doctor sees all their patients
            $patients = PatientDoctor::where('doctor_id', $user->id)
                ->with(['patient.seizures', 'patient.vitalSigns'])
                ->get()
                ->map(function ($relation) {
                    $patient = $relation->patient;
                    if (!$patient) return null;

                    $latestSeizure = $patient->seizures->sortByDesc('start_time')->first();
                    return [
                        'id' => $patient->id,
                        'name' => $patient->name,
                        'phone' => $patient->phone,
                        'address' => $patient->address,
                        'latitude' => $latestSeizure?->latitude ?? 24.7136,
                        'longitude' => $latestSeizure?->longitude ?? 46.6753,
                        'status' => $latestSeizure && !$latestSeizure->end_time ? 'alert' : 'stable',
                        'seizures' => $patient->seizures,
                    ];
                })->filter();
        } elseif ($user->role === 'family') {
            // Family sees their connected patients
            $patients = EmergencyContact::where('status', 'accepted')
                ->where('contact_user_id', $user->id)
                ->with(['user.seizures', 'user.vitalSigns'])
                ->get()
                ->map(function ($contact) {
                    $patient = $contact->user;
                    if (!$patient) return null;

                    $latestSeizure = $patient->seizures->sortByDesc('start_time')->first();
                    return [
                        'id' => $patient->id,
                        'name' => $patient->name,
                        'phone' => $patient->phone,
                        'address' => $patient->address,
                        'latitude' => $latestSeizure?->latitude ?? 24.7136,
                        'longitude' => $latestSeizure?->longitude ?? 46.6753,
                        'status' => $latestSeizure && !$latestSeizure->end_time ? 'alert' : 'stable',
                        'seizures' => $patient->seizures,
                    ];
                })->filter();
        } else {
            // Patient sees their own data
            $user->load('seizures');
            $latestSeizure = $user->seizures->sortByDesc('start_time')->first();
            $patients = collect([[
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'address' => $user->address,
                'latitude' => $latestSeizure?->latitude ?? 24.7136,
                'longitude' => $latestSeizure?->longitude ?? 46.6753,
                'status' => $latestSeizure && !$latestSeizure->end_time ? 'alert' : 'stable',
                'seizures' => $user->seizures,
            ]]);
        }

        $unreadNotifications = AppNotification::where('user_id', $user->id)->where('is_read', false)->count();

        return view('dashboards.map', compact('patients', 'unreadNotifications'));
    }

    public function family()
    {
        $user = Auth::user();
        $patients = collect();
        $contacts = collect();
        $pendingRequests = collect();
        $notifications = collect();

        if ($user->role === 'family') {
            $patients = EmergencyContact::where('status', 'accepted')
                ->where('contact_user_id', $user->id)
                ->with([
                    'user.seizures',
                    'user.vitalSigns',
                ])
                ->get()
                ->map(function ($contact) {
                    $patient = $contact->user;
                    if (!$patient) {
                        return null;
                    }

                    $latestSeizure = $patient->seizures->sortByDesc('start_time')->first();
                    $latestVital = $patient->vitalSigns->sortByDesc('created_at')->first();
                    $latestReadingParts = array_filter([
                        $latestVital?->heart_rate ? 'معدل النبض ' . $latestVital->heart_rate : null,
                        $latestVital?->oxygen_level ? 'تشبع الأكسجين ' . $latestVital->oxygen_level . '%' : null,
                        $latestVital?->temperature ? 'الحرارة ' . $latestVital->temperature . '°C' : null,
                        $latestVital?->eeg_signal ? 'EEG ' . $latestVital->eeg_signal : null,
                        $latestVital?->emg_signal ? 'EMG ' . $latestVital->emg_signal : null,
                    ]);

                    return [
                        'id' => $patient->id,
                        'name' => $patient->name,
                        'email' => $patient->email,
                        'phone' => $patient->phone,
                        'address' => $patient->address,
                        'relationship' => $contact->relationship,
                        'latitude' => $latestSeizure?->latitude,
                        'longitude' => $latestSeizure?->longitude,
                        'status' => $patient->seizures->whereNull('end_time')->count() ? 'alert' : 'stable',
                        'last_update' => $latestVital?->created_at->diffForHumans() ?? 'لا يوجد',
                        'latest_reading' => !empty($latestReadingParts) ? implode(' • ', $latestReadingParts) : 'لا توجد قراءة حديثة',
                        'active_seizures' => $patient->seizures->whereNull('end_time')->count(),
                        'seizures' => $patient->seizures,
                    ];
                })
                ->filter()
                ->values();

            // Get pending requests for family members
            $pendingRequests = EmergencyContact::where('contact_user_id', $user->id)
                ->where('status', 'pending')
                ->with('user')
                ->get()
                ->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'name' => $contact->user->name,
                        'email' => $contact->user->email,
                        'phone' => $contact->phone,
                        'relationship' => $contact->relationship,
                    ];
                });

            $patientIds = $patients->pluck('id');
            $notifications = AppNotification::whereIn('user_id', $patientIds)
                ->latest()
                ->take(20)
                ->get()
                ->map(function ($notification) use ($patients) {
                    $patient = $patients->firstWhere('id', $notification->user_id);
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => $notification->type,
                        'patient_name' => $patient ? $patient['name'] : 'غير معروف',
                        'created_at' => $notification->created_at,
                        'is_read' => $notification->is_read,
                    ];
                });
        }

        if ($user->role === 'patient') {
            $contacts = $user->emergencyContacts()->get();
        }

        return view('dashboards.family', compact('patients', 'contacts', 'pendingRequests', 'notifications'));
    }

    public function storeFamilyRequest(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|exists:users,email',
            'phone' => 'nullable|string|max:20',
            'relationship' => 'required|string|max:100',
        ], [
            'email.exists' => 'يجب أن يكون هذا البريد مسجلاً في النظام كمستخدم أفراد عائلة.',
        ]);

        $contactUser = User::where('email', $request->email)->where('role', 'family')->first();
        if (!$contactUser) {
            return redirect()->route('family')
                ->withErrors(['email' => 'لم يتم العثور على حساب فرد عائلة بهذا البريد. تأكد من تسجيله أولاً.'])
                ->withInput();
        }

        if ($user->emergencyContacts()->where('contact_user_id', $contactUser->id)->exists()) {
            return redirect()->route('family')
                ->with('success', 'تم بالفعل إرسال طلب ارتباط لهذا الفرد من العائلة.');
        }

        $user->emergencyContacts()->create([
            'name' => $request->name,
            'phone' => $request->phone ?: $contactUser->phone,
            'relationship' => $request->relationship,
            'contact_user_id' => $contactUser->id,
            'notify_on_prediction' => true,
            'notify_on_seizure' => true,
        ]);

        return redirect()->route('family')->with('success', 'تم إرسال طلب الارتباط بفرد العائلة بنجاح. سيتم إخطار هذا الشخص عند حدوث نوبة.');
    }

    public function acceptFamilyRequest(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'request_id' => 'required|exists:emergency_contacts,id',
        ]);

        $contact = EmergencyContact::find($request->request_id);

        if (!$contact || $contact->contact_user_id !== $user->id) {
            return redirect()->route('family')->withErrors(['request' => 'طلب غير صالح']);
        }

        $contact->update(['status' => 'accepted']);

        return redirect()->route('family')->with('success', 'تم قبول طلب الارتباط بنجاح. يمكنك الآن متابعة حالة المريض.');
    }

    public function rejectFamilyRequest(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'request_id' => 'required|exists:emergency_contacts,id',
        ]);

        $contact = EmergencyContact::find($request->request_id);

        if (!$contact || $contact->contact_user_id !== $user->id) {
            return redirect()->route('family')->withErrors(['request' => 'طلب غير صالح']);
        }

        $contact->delete();

        return redirect()->route('family')->with('success', 'تم رفض طلب الارتباط.');
    }

    public function doctor()
    {
        $user = Auth::user();
        $patients = collect();
        $predictionRate = 0;
        $avgSeizureDuration = 0;
        $availableDoctors = collect();
        $linkedDoctors = collect();

        $activeSeizureCount = 0;
        $linkedDoctorsCount = 0;
        $totalSeizures = 0;
        $activePatientCount = 0;

        if ($user->role === 'doctor') {
            $patients = $user->patients()->with('vitalSigns', 'seizures')->get();
            $patientIds = $patients->pluck('id')->all();

            if (!empty($patientIds)) {
                $totalSeizures = Seizure::whereIn('user_id', $patientIds)->count();
                $predictedSeizures = Seizure::whereIn('user_id', $patientIds)->where('is_predicted', true)->count();
                $predictionRate = $totalSeizures > 0 ? round($predictedSeizures / $totalSeizures * 100, 2) : 0;
                $avgSeizureDuration = (float) Seizure::whereIn('user_id', $patientIds)
                    ->whereNotNull('end_time')
                    ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, start_time, end_time)'));
                $activeSeizureCount = Seizure::whereIn('user_id', $patientIds)->whereNull('end_time')->count();
                $activePatientCount = $patients->filter(fn($p) => $p->seizures()->whereNull('end_time')->exists())->count();
            }
        }

        if ($user->role === 'patient') {
            $availableDoctors = User::where('role', 'doctor')->get();
            $linkedDoctors = $user->doctors()->get();
            $linkedDoctorsCount = $linkedDoctors->count();
        }

        return view('dashboards.doctor', compact('patients', 'predictionRate', 'avgSeizureDuration', 'availableDoctors', 'linkedDoctors', 'activeSeizureCount', 'linkedDoctorsCount', 'totalSeizures', 'activePatientCount'));
    }

    public function doctorMonitor(?User $patient = null)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'doctor') {
            return redirect()->route('dashboard');
        }

        $patients = $user->patients()
            ->with(['vitalSigns' => fn($query) => $query->latest()->take(1), 'seizures' => fn($query) => $query->latest()->take(5), 'devices'])
            ->get()
            ->map(function ($patientModel) {
                $latestVital = $patientModel->vitalSigns->first();
                $activeSeizure = $patientModel->seizures->firstWhere(fn($seizure) => is_null($seizure->end_time));

                $patientModel->latest_vitals = $latestVital;
                $patientModel->status_text = $activeSeizure ? 'نوبة نشطة' : 'مستقر';
                $patientModel->high_risk = $activeSeizure || ($latestVital && ($latestVital->heart_rate ?? 0) > 100);

                return $patientModel;
            });

        $currentPatient = $patient
            ? $patients->firstWhere('id', $patient->id)
            : $patients->first();

        $currentVitals = optional($currentPatient)->latest_vitals;

        $analysisRisk = $currentVitals
            ? min(0.95, max(0.05, (($currentVitals->heart_rate ?? 72) - 60) / 180 + (($currentVitals->temperature ?? 37) - 36) * 0.03 + (($currentVitals->oxygen_level ?? 98) - 98) * -0.01))
            : 0.15;

        $analysis = (object) [
            'risk_score' => $analysisRisk,
            'alert_level' => optional($currentPatient)->high_risk ? 'emergency' : 'stable',
        ];

        $alerts = optional($currentPatient)->seizures?->take(5) ?? collect();
        $activeAlertsCount = $alerts->count();
        $predictionPercent = round($analysisRisk * 100);
        $totalPatients = $patients->count();

        return view('dashboards.doctor-medical-monitor', compact(
            'patients',
            'currentPatient',
            'currentVitals',
            'analysis',
            'alerts',
            'activeAlertsCount',
            'predictionPercent',
            'totalPatients'
        ));
    }

    public function doctorReports()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'doctor') {
            return redirect()->route('dashboard');
        }

        $patients = $user->patients()
            ->with(['vitalSigns' => fn($query) => $query->latest()->take(1), 'seizures'])
            ->get()
            ->map(function ($patient) {
                $latestVital = $patient->vitalSigns->first();
                $latestSeizure = $patient->seizures->sortByDesc('start_time')->first();

                $patient->latest_vitals = $latestVital;
                $patient->latest_seizure = $latestSeizure;
                $patient->active_seizure = $patient->seizures->firstWhere(fn($seizure) => is_null($seizure->end_time));

                return $patient;
            });

        $totalPatients = $patients->count();
        $activePatients = $patients->filter(fn($patient) => !is_null($patient->active_seizure))->count();
        $totalSeizures = $patients->sum(fn($patient) => $patient->seizures->count());
        $predictedSeizures = $patients->sum(fn($patient) => $patient->seizures->where('is_predicted', true)->count());
        $averageDuration = $patients->flatMap(fn($patient) => $patient->seizures->whereNotNull('end_time'))
            ->map(fn($seizure) => $seizure->end_time->diffInMinutes($seizure->start_time))
            ->avg();
        $averageHeartRate = round($patients->flatMap(fn($patient) => $patient->vitalSigns)->avg('heart_rate') ?? 0, 1);
        $averageOxygen = round($patients->flatMap(fn($patient) => $patient->vitalSigns)->avg('oxygen_level') ?? 0, 1);
        $predictionRate = $totalSeizures > 0 ? round(($predictedSeizures / $totalSeizures) * 100, 1) : 0;

        return view('dashboards.doctor-reports', compact(
            'patients',
            'totalPatients',
            'activePatients',
            'totalSeizures',
            'predictionRate',
            'averageDuration',
            'averageHeartRate',
            'averageOxygen'
        ));
    }

    public function storeDoctorRequest(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'doctor_id' => 'required|exists:users,id',
        ]);

        $doctor = User::where('role', 'doctor')->find($request->doctor_id);
        if (!$doctor) {
            return redirect()->route('doctor')->withErrors(['doctor_id' => 'الطبيب غير موجود أو غير مسجل.']);
        }

        if ($user->doctors()->where('doctor_id', $doctor->id)->exists()) {
            return redirect()->route('doctor')->with('success', 'أنت بالفعل مرتبط بهذا الطبيب.');
        }

        $user->doctors()->attach($doctor->id);

        return redirect()->route('doctor')->with('success', 'تم إرسال طلب الارتباط بالطبيب. سيتم إشعار الطبيب عند حدوث نوبة.');
    }

    public function settings()
    {
        $user = Auth::user();
        $contacts = $user->emergencyContacts()->get();
        $devices = $user->devices()->get();

        return view('dashboards.settings', compact('user', 'contacts', 'devices'));
    }
    /**
     * Get AI-powered analysis for patient dashboard
     */
    public function getAIAnalysis()
    {
        $user = Auth::user();

        // Get latest device data
        $devices = Device::where('user_id', $user->id)->where('status', 'connected')->get();
        $deviceData = [];

        foreach ($devices as $device) {
            $latestData = $device->getLastDataAttribute();
            if ($latestData) {
                $deviceData[$device->type] = $latestData;
            }
        }

        // Use AI service for analysis
        $predictionService = app(SeizurePrediction::class);
        $analysis = $predictionService->analyzeDeviceData($user, $deviceData);

        return response()->json([
            'activity' => $analysis['activity'],
            'risk_level' => $analysis['risk_level'],
            'probability' => $analysis['probability'],
            'time_to_event' => $analysis['time_to_event'],
            'recommendations' => $analysis['recommendations'],
            'ai_explanation' => $analysis['ai_explanation'] ?? null
        ]);
    }
    private function calculateRiskScore($userId)
    {
        $latestVitals = VitalSign::where('user_id', $userId)->latest()->first();
        $baseScore = 50;

        if ($latestVitals) {
            $baseScore += intval(($latestVitals->heart_rate ?? 70) - 70) * 0.3;
            $baseScore -= intval(($latestVitals->oxygen_level ?? 98) - 98) * 0.5;
            $baseScore += intval(($latestVitals->temperature ?? 37) - 37) * 2;
        }

        $recentPredicted = Seizure::where('user_id', $userId)->where('is_predicted', true)->count();
        $riskScore = min(95, max(10, round($baseScore + $recentPredicted * 5)));

        return $riskScore;
    }

    private function getSleepQualityText($quality)
    {
        return match($quality) {
            1 => 'سيئة جداً',
            2 => 'سيئة',
            3 => 'متوسطة',
            4 => 'جيدة',
            5 => 'ممتازة',
            default => 'غير محدد'
        };
    }

    private function getStressLevelText($stress)
    {
        return match($stress) {
            1 => 'منخفض جداً',
            2 => 'منخفض',
            3 => 'متوسط',
            4 => 'عالي',
            5 => 'عالي جداً',
            default => 'غير محدد'
        };
    }

    private function getActivityLevelText($activity)
    {
        return match(strtolower($activity)) {
            'low', 'منخفض' => 'منخفض',
            'medium', 'متوسط' => 'متوسط',
            'high', 'نشط' => 'نشط',
            default => 'غير محدد'
        };
    }

    public function sendPatientNotification(Request $request, $patientId)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $doctor = Auth::user();
        $patient = User::findOrFail($patientId);

        // التحقق من أن الطبيب مرتبط بالمريض
        if (!$doctor->patients()->where('patient_id', $patientId)->exists()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بإرسال إشعارات لهذا المريض']);
        }

        // إنشاء إشعار
        AppNotification::create([
            'user_id' => $patientId,
            'title' => 'إشعار من الطبيب',
            'message' => $request->message,
            'type' => 'doctor_message',
            'is_read' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'تم إرسال الإشعار بنجاح']);
    }

    public function addPatientNote(Request $request, $patientId)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $doctor = Auth::user();
        $patient = User::findOrFail($patientId);

        // التحقق من أن الطبيب مرتبط بالمريض
        if (!$doctor->patients()->where('patient_id', $patientId)->exists()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بإضافة ملاحظات لهذا المريض']);
        }

        // إضافة الملاحظة كإشعار أو حفظ في مكان آخر
        // يمكن حفظها في جدول منفصل أو كإشعار
        AppNotification::create([
            'user_id' => $patientId,
            'title' => 'ملاحظة طبية',
            'message' => $request->note,
            'type' => 'medical_note',
            'is_read' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'تم إضافة الملاحظة بنجاح']);
    }

    public function patientDetails($patientId)
    {
        $user = Auth::user();
        $patient = User::findOrFail($patientId);

        // التحقق من الصلاحيات
        if ($user->role === 'doctor') {
            if (!$user->patients()->where('patient_id', $patientId)->exists()) {
                abort(403, 'غير مصرح لك بعرض تفاصيل هذا المريض');
            }
        } elseif ($user->role === 'family') {
            if (!EmergencyContact::where('contact_user_id', $user->id)
                                ->where('user_id', $patientId)
                                ->where('status', 'accepted')
                                ->exists()) {
                abort(403, 'غير مصرح لك بعرض تفاصيل هذا المريض');
            }
        } elseif ($user->role === 'patient') {
            if ($user->id !== $patientId) {
                abort(403, 'يمكنك فقط عرض تفاصيل حسابك الخاص');
            }
        } else {
            abort(403, 'غير مصرح لك بعرض تفاصيل المرضى');
        }

        // جمع بيانات المريض
        $patient->load(['seizures', 'vitalSigns', 'devices', 'dailyEntries']);

        $latestSeizure = $patient->seizures->sortByDesc('start_time')->first();
        $latestVitals = $patient->vitalSigns->sortByDesc('created_at')->first();
        $totalSeizures = $patient->seizures->count();
        $activeSeizure = $patient->seizures->whereNull('end_time')->first();

        $seizureStats = [
            'total' => $totalSeizures,
            'this_month' => $patient->seizures->where('created_at', '>=', now()->startOfMonth())->count(),
            'this_week' => $patient->seizures->where('created_at', '>=', now()->startOfWeek())->count(),
            'average_duration' => $patient->seizures->whereNotNull('end_time')->avg(function ($seizure) {
                return $seizure->end_time->diffInMinutes($seizure->start_time);
            }) ?? 0,
        ];

        $vitalsStats = [
            'avg_heart_rate' => $patient->vitalSigns->avg('heart_rate') ?? 0,
            'avg_oxygen' => $patient->vitalSigns->avg('oxygen_level') ?? 0,
            'avg_temperature' => $patient->vitalSigns->avg('temperature') ?? 0,
            'latest_heart_rate' => $latestVitals->heart_rate ?? null,
            'latest_oxygen' => $latestVitals->oxygen_level ?? null,
            'latest_temperature' => $latestVitals->temperature ?? null,
        ];

        $recentSeizures = $patient->seizures->sortByDesc('start_time')->take(10);
        $recentVitals = $patient->vitalSigns->sortByDesc('created_at')->take(10);
        $recentEntries = $patient->dailyEntries->sortByDesc('created_at')->take(10);

        return view('dashboards.patient-details', compact(
            'patient',
            'latestSeizure',
            'latestVitals',
            'activeSeizure',
            'seizureStats',
            'vitalsStats',
            'recentSeizures',
            'recentVitals',
            'recentEntries'
        ));
    }
}
