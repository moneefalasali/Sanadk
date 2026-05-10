<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Seizure;
use App\Models\VitalSign;
use App\Models\User;
use App\Models\Device;
use App\Models\DailyEntry;
use App\Models\AppNotification;
use App\Models\EmergencyContact;
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
            return view('dashboards.doctor', compact('patients', 'activePatientCount', 'totalSeizures'));
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
        $request->validate([
            'device_id' => 'required|exists:devices,id',
        ]);

        $device = Device::where('id', $request->device_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

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
                    if (isset($data['oxygen_level'])) $liveData['oxygen_level'] = $data['oxygen_level'];
                    if (isset($data['temperature'])) $liveData['temperature'] = $data['temperature'];
                } elseif ($device->type === 'emg') {
                    if (isset($data['tension'])) {
                        $liveData['muscle_tension'] = $data['tension'];
                    }
                    if (isset($data['nerve_signals'])) {
                        $liveData['nerve_signals'] = $data['nerve_signals'];
                    }
                } elseif ($device->type === 'eeg' && isset($data['activity_level'])) {
                    $liveData['brain_activity'] = $data['activity_level'];
                }

                if (!$liveData['last_updated'] || $device->updated_at > $liveData['last_updated']) {
                    $liveData['last_updated'] = $device->updated_at;
                }
            }
        }

        return response()->json($liveData);
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
        $user->load('seizures');
        $latestSeizure = $user->seizures->sortByDesc('start_time')->first();

        $patients = collect([[
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'address' => $user->address,
            'latitude' => $latestSeizure?->latitude,
            'longitude' => $latestSeizure?->longitude,
            'status' => $latestSeizure && !$latestSeizure->end_time ? 'alert' : 'stable',
            'seizures' => $user->seizures,
        ]]);

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

    private function getRiskAdviceText($riskScore)
    {
        if ($riskScore >= 70) {
            return 'احرص على الراحة وتواصل مع الطبيب إذا استمر الخطر مرتفعًا.';
        }

        if ($riskScore >= 40) {
            return 'احرص على تناول الأدوية وراحة إضافية، وتابع حالتك خلال اليوم.';
        }

        return 'الحالة مستقرة حالياً، حافظ على نمط صحي وتابع إدخال بياناتك اليومية.';
    }
}
