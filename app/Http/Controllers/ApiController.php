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
            // Get user location from request (required)
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');

            if (!$latitude || !$longitude) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تحديد الموقع (latitude و longitude)'
                ], 400);
            }

            // Get hospitals from OpenStreetMap Overpass API only
            $osmHospitals = $this->searchHospitalsOSM($latitude, $longitude);

            if (!empty($osmHospitals)) {
                // Sort by distance
                usort($osmHospitals, function($a, $b) {
                    return $a['distance'] <=> $b['distance'];
                });

                return response()->json([
                    'success' => true,
                    'hospitals' => array_slice($osmHospitals, 0, 10),
                    'user_location' => [
                        'lat' => (float)$latitude,
                        'lng' => (float)$longitude
                    ],
                    'source' => 'osm',
                    'total' => count($osmHospitals)
                ]);
            }

            // No hospitals found
            return response()->json([
                'success' => true,
                'hospitals' => [],
                'user_location' => [
                    'lat' => (float)$latitude,
                    'lng' => (float)$longitude
                ],
                'message' => 'لم يتم العثور على مستشفيات قريبة',
                'source' => 'osm'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في تحميل البيانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function searchHospitalsOSM($latitude, $longitude, $radiusKm = 15)
    {
        try {
            // Convert radius to degrees (approximate)
            $radiusDeg = $radiusKm / 111.32; // 1 degree ≈ 111.32 km

            // Overpass API query for hospitals
            $south = $latitude - $radiusDeg;
            $west = $longitude - $radiusDeg;
            $north = $latitude + $radiusDeg;
            $east = $longitude + $radiusDeg;

            $query = "[out:json][timeout:30];\n"
                . "(\n"
                . "  node['amenity'='hospital']({$south},{$west},{$north},{$east});\n"
                . "  way['amenity'='hospital']({$south},{$west},{$north},{$east});\n"
                . "  relation['amenity'='hospital']({$south},{$west},{$north},{$east});\n"
                . "  node['amenity'='clinic']({$south},{$west},{$north},{$east});\n"
                . "  way['amenity'='clinic']({$south},{$west},{$north},{$east});\n"
                . "  node['healthcare']({$south},{$west},{$north},{$east});\n"
                . ");\n"
                . "out center;\n";

            $url = 'https://overpass-api.de/api/interpreter?data=' . urlencode($query);

            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'SANADAK-HospitalSearch/1.0'
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return [];
            }

            $data = json_decode($response, true);

            if (!$data || !isset($data['elements'])) {
                return [];
            }

            $hospitals = [];
            foreach ($data['elements'] as $element) {
                if (isset($element['tags']['name'])) {
                    $lat = $element['lat'] ?? ($element['center']['lat'] ?? null);
                    $lng = $element['lon'] ?? ($element['center']['lon'] ?? null);

                    if ($lat && $lng) {
                        $distance = $this->calculateDistance($latitude, $longitude, $lat, $lng);

                        // Skip hospitals too far away
                        if ($distance > $radiusKm) {
                            continue;
                        }

                        $hospitals[] = [
                            'name' => $element['tags']['name'],
                            'lat' => (float)$lat,
                            'lng' => (float)$lng,
                            'distance' => round($distance, 1),
                            'eta' => $this->calculateETA($distance),
                            'address' => $this->buildAddress($element['tags']),
                            'phone' => $element['tags']['phone'] ?? $element['tags']['contact:phone'] ?? null,
                            'type' => $this->getHospitalType($element['tags']),
                            'specialties' => $this->extractSpecialties($element['tags']),
                            'source' => 'osm'
                        ];
                    }
                }
            }

            return $hospitals;

        } catch (\Exception $e) {
            \Log::error('OSM Hospital Search Error: ' . $e->getMessage());
            return [];
        }
    }

    private function buildAddress($tags)
    {
        $parts = [];
        if (isset($tags['addr:street'])) $parts[] = $tags['addr:street'];
        if (isset($tags['addr:city'])) $parts[] = $tags['addr:city'];
        if (isset($tags['addr:country'])) $parts[] = $tags['addr:country'];
        
        return !empty($parts) ? implode(', ', $parts) : 'المملكة العربية السعودية';
    }

    private function extractSpecialties($tags)
    {
        $specialties = [];

        if (isset($tags['healthcare:speciality'])) {
            $specs = explode(';', $tags['healthcare:speciality']);
            foreach ($specs as $spec) {
                $specialties[] = trim($spec);
            }
        }

        if (isset($tags['emergency']) && $tags['emergency'] === 'yes') {
            $specialties[] = 'طوارئ';
        }

        return array_unique($specialties);
    }

    private function getHospitalType($tags)
    {
        if (isset($tags['healthcare'])) {
            $healthcare = strtolower($tags['healthcare']);
            if (strpos($healthcare, 'hospital') !== false) return 'مستشفى';
            if (strpos($healthcare, 'clinic') !== false) return 'عيادة';
            if (strpos($healthcare, 'doctor') !== false) return 'عيادة طبيب';
        }

        if (isset($tags['amenity'])) {
            if ($tags['amenity'] === 'hospital') return 'مستشفى';
            if ($tags['amenity'] === 'clinic') return 'عيادة';
        }

        return 'مرفق طبي';
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
