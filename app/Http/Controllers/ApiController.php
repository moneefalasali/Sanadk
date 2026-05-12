<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VitalSign;
use App\Models\Seizure;
use App\Services\OpenAIService;

class ApiController extends Controller
{
    public function updateVitals(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'heart_rate' => 'required|numeric',
            'oxygen_level' => 'required|numeric',
            'temperature' => 'nullable|numeric',
        ]);

        $vitals = VitalSign::create($request->all());

        // Run AI Detection
        $detector = new SeizureDetector();
        $isSeizure = $detector->analyze($vitals);

        if ($isSeizure) {
            Seizure::create([
                'user_id' => $request->user_id,
                'start_time' => now(),
                'is_predicted' => false,
            ]);
            return response()->json(['status' => 'alert', 'message' => 'Seizure detected!']);
        }

        return response()->json(['status' => 'ok', 'data' => $vitals]);
    }

    public function getPatientStatus($id)
    {
        $user = User::findOrFail($id);
        $latestVitals = VitalSign::where('user_id', $id)->latest()->first();
        $activeSeizure = Seizure::where('user_id', $id)->whereNull('end_time')->first();

        return response()->json([
            'name' => $user->name,
            'vitals' => $latestVitals,
            'is_in_seizure' => $activeSeizure ? true : false
        ]);
    }

    public function searchHospitalsAI(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location' => 'required|string',
        ]);

        try {
            $openAI = new OpenAIService();
            $hospitals = $openAI->searchNearbyHospitals($request->latitude, $request->longitude, $request->location);

            return response()->json([
                'success' => true,
                'hospitals' => $hospitals
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في البحث عن المستشفيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getNearbyHospitals(Request $request)
    {
        try {
            // Get user location from request or use default (Riyadh)
            $latitude = $request->input('latitude', 24.7136);
            $longitude = $request->input('longitude', 46.6753);

            // Static hospital data for Saudi Arabia (Riyadh area)
            $hospitals = [
                [
                    'name' => 'مستشفى الملك فيصل التخصصي',
                    'lat' => 24.7133,
                    'lng' => 46.6840,
                    'distance' => $this->calculateDistance($latitude, $longitude, 24.7133, 46.6840),
                    'eta' => $this->calculateETA($this->calculateDistance($latitude, $longitude, 24.7133, 46.6840)),
                    'address' => 'الرياض، المملكة العربية السعودية',
                    'phone' => '+966114424000',
                    'type' => 'تخصصي'
                ],
                [
                    'name' => 'مستشفى الحرس الوطني',
                    'lat' => 24.7040,
                    'lng' => 46.6908,
                    'distance' => $this->calculateDistance($latitude, $longitude, 24.7040, 46.6908),
                    'eta' => $this->calculateETA($this->calculateDistance($latitude, $longitude, 24.7040, 46.6908)),
                    'address' => 'الرياض، المملكة العربية السعودية',
                    'phone' => '+966114411111',
                    'type' => 'عام'
                ],
                [
                    'name' => 'مدينة الملك عبدالعزيز الطبية',
                    'lat' => 24.6969,
                    'lng' => 46.7500,
                    'distance' => $this->calculateDistance($latitude, $longitude, 24.6969, 46.7500),
                    'eta' => $this->calculateETA($this->calculateDistance($latitude, $longitude, 24.6969, 46.7500)),
                    'address' => 'الرياض، المملكة العربية السعودية',
                    'phone' => '+966114888888',
                    'type' => 'تعليمي'
                ],
                [
                    'name' => 'مستشفى الملك خالد الجامعي',
                    'lat' => 24.7170,
                    'lng' => 46.6250,
                    'distance' => $this->calculateDistance($latitude, $longitude, 24.7170, 46.6250),
                    'eta' => $this->calculateETA($this->calculateDistance($latitude, $longitude, 24.7170, 46.6250)),
                    'address' => 'الرياض، المملكة العربية السعودية',
                    'phone' => '+966114670000',
                    'type' => 'جامعي'
                ],
                [
                    'name' => 'مستشفى الأمير سلطان',
                    'lat' => 24.7250,
                    'lng' => 46.6800,
                    'distance' => $this->calculateDistance($latitude, $longitude, 24.7250, 46.6800),
                    'eta' => $this->calculateETA($this->calculateDistance($latitude, $longitude, 24.7250, 46.6800)),
                    'address' => 'الرياض، المملكة العربية السعودية',
                    'phone' => '+966114444444',
                    'type' => 'عسكري'
                ]
            ];

            // Sort by distance
            usort($hospitals, function($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });

            return response()->json([
                'success' => true,
                'hospitals' => array_slice($hospitals, 0, 5), // Return top 5 nearest
                'user_location' => [
                    'lat' => $latitude,
                    'lng' => $longitude
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل بيانات المستشفيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return round($distance, 1);
    }

    private function calculateETA($distanceKm)
    {
        // Assume average speed of 40 km/h in city traffic
        $hours = $distanceKm / 40;
        $minutes = round($hours * 60);

        if ($minutes < 60) {
            return $minutes . ' دقيقة';
        } else {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return $hours . ' ساعة ' . ($remainingMinutes > 0 ? $remainingMinutes . ' دقيقة' : '');
        }
    }
}
