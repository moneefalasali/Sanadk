<?php

namespace App\Services;

use OpenAI\Client;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $client;

    public function __construct()
    {
        $this->client = \OpenAI::client(config('services.openai.api_key'));
    }

    /**
     * Analyze patient activity using AI
     */
    public function analyzeActivity(array $deviceData, array $patientHistory = [])
    {
        try {
            $prompt = $this->buildActivityPrompt($deviceData, $patientHistory);

            $response = $this->client->chat()->create([
                'model' => config('services.openai.model', 'gpt-4'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت طبيب متخصص في الصرع وتحليل البيانات الطبية. قدم تحليلاً دقيقاً ومفيداً باللغة العربية.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3
            ]);

            $analysis = $response->choices[0]->message->content;

            return $this->parseActivityAnalysis($analysis);

        } catch (\Exception $e) {
            Log::error('OpenAI Activity Analysis Error: ' . $e->getMessage());
            return $this->getFallbackActivityAnalysis($deviceData);
        }
    }

    /**
     * Predict seizure risk using AI
     */
    public function predictSeizureRisk(array $deviceData, array $patientHistory = [])
    {
        try {
            $prompt = $this->buildRiskPredictionPrompt($deviceData, $patientHistory);

            $response = $this->client->chat()->create([
                'model' => config('services.openai.model', 'gpt-4'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت متخصص في التنبؤ بالنوبات الصرعية. قدم تقييماً دقيقاً للخطر بناءً على البيانات الطبية.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 800,
                'temperature' => 0.2
            ]);

            $prediction = $response->choices[0]->message->content;

            return $this->parseRiskPrediction($prediction);

        } catch (\Exception $e) {
            Log::error('OpenAI Risk Prediction Error: ' . $e->getMessage());
            return $this->getFallbackRiskPrediction($deviceData);
        }
    }

    /**
     * Generate medical recommendations
     */
    public function generateRecommendations(array $deviceData, string $riskLevel, array $patientHistory = [])
    {
        try {
            $prompt = $this->buildRecommendationsPrompt($deviceData, $riskLevel, $patientHistory);

            $response = $this->client->chat()->create([
                'model' => config('services.openai.model', 'gpt-4'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت طبيب متخصص في الصرع. قدم نصائح طبية مفيدة وآمنة باللغة العربية.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 600,
                'temperature' => 0.4
            ]);

            $recommendations = $response->choices[0]->message->content;

            return $this->parseRecommendations($recommendations);

        } catch (\Exception $e) {
            Log::error('OpenAI Recommendations Error: ' . $e->getMessage());
            return $this->getFallbackRecommendations($riskLevel);
        }
    }

    /**
     * Find nearby hospitals using AI (location-based search)
     */
    public function findNearbyHospitals($latitude, $longitude, array $deviceData = [])
    {
        try {
            $prompt = $this->buildHospitalsPrompt($latitude, $longitude, $deviceData);

            $response = $this->client->chat()->create([
                'model' => config('services.openai.model', 'gpt-4'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت متخصص في الخدمات الطبية. قدم معلومات دقيقة عن المستشفيات القريبة.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.1
            ]);

            $hospitalsInfo = $response->choices[0]->message->content;

            return $this->parseHospitalsInfo($hospitalsInfo);

        } catch (\Exception $e) {
            Log::error('OpenAI Hospitals Search Error: ' . $e->getMessage());
            return $this->getFallbackHospitals($latitude, $longitude);
        }
    }

    /**
     * Search nearby hospitals using AI-enhanced OSM search
     */
    public function searchNearbyHospitals($latitude, $longitude, $location = null)
    {
        try {
            // Get hospitals from OSM only
            $osmHospitals = $this->searchHospitalsOSM($latitude, $longitude);

            if (!empty($osmHospitals)) {
                // Use AI to enhance the results with additional information
                return $this->enhanceHospitalsWithAI($osmHospitals, $latitude, $longitude);
            }

            // Return empty array if no hospitals found (no fallback)
            return [];

        } catch (\Exception $e) {
            Log::error('Hospital Search Error: ' . $e->getMessage());
            return [];
        }
    }

    private function searchHospitalsOSM($latitude, $longitude, $radiusKm = 10)
    {
        try {
            // Convert radius to degrees (approximate)
            $radiusDeg = $radiusKm / 111.32; // 1 degree ≈ 111.32 km

            // Overpass API query for hospitals and clinics
            $south = $latitude - $radiusDeg;
            $west = $longitude - $radiusDeg;
            $north = $latitude + $radiusDeg;
            $east = $longitude + $radiusDeg;

            $query = "[out:json][timeout:25];\n"
                . "(\n"
                . "  node['amenity'='hospital']({$south},{$west},{$north},{$east});\n"
                . "  way['amenity'='hospital']({$south},{$west},{$north},{$east});\n"
                . "  relation['amenity'='hospital']({$south},{$west},{$north},{$east});\n"
                . "  node['amenity'='clinic']({$south},{$west},{$north},{$east});\n"
                . "  way['amenity'='clinic']({$south},{$west},{$north},{$east});\n"
                . "  node['healthcare'='hospital']({$south},{$west},{$north},{$east});\n"
                . "  way['healthcare'='hospital']({$south},{$west},{$north},{$east});\n"
                . ");\n"
                . "out center;\n";

            $url = 'https://overpass-api.de/api/interpreter?data=' . urlencode($query);

            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'user_agent' => 'SANADAK-HospitalSearch/1.0'
                ]
            ]);

            $response = file_get_contents($url, false, $context);

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
                            'lat' => $lat,
                            'lng' => $lng,
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

            // Sort by distance
            usort($hospitals, function($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });

            return array_slice($hospitals, 0, 8); // Return top 8

        } catch (\Exception $e) {
            Log::warning('OSM Hospital Search Error: ' . $e->getMessage());
            return [];
        }
    }

    private function enhanceHospitalsWithAI($hospitals, $latitude, $longitude)
    {
        try {
            // Use AI to add additional information and prioritize epilepsy-specialized hospitals
            $prompt = "بناءً على قائمة المستشفيات التالية في المنطقة (الرياض، المملكة العربية السعودية):\n\n";

            foreach ($hospitals as $index => $hospital) {
                $prompt .= ($index + 1) . ". {$hospital['name']} - {$hospital['type']} - {$hospital['distance']} كم\n";
            }

            $prompt .= "\nقم بما يلي:\n";
            $prompt .= "1. حدد أولوية المستشفيات المناسبة لعلاج الصرع والطوارئ العصبية\n";
            $prompt .= "2. أضف معلومات إضافية عن التخصصات إن أمكن\n";
            $prompt .= "3. رتبها حسب الأولوية للحالات الطارئة\n\n";
            $prompt .= "قدم القائمة مع الترتيب الجديد والأولويات.";

            $response = $this->client->chat()->create([
                'model' => config('services.openai.model', 'gpt-4'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت متخصص في الخدمات الطبية الطارئة. ركز على تحديد المستشفيات المناسبة لحالات الصرع والطوارئ العصبية.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 800,
                'temperature' => 0.1
            ]);

            $aiAnalysis = $response->choices[0]->message->content;

            // For now, just return the OSM results with AI enhancement flag
            // In a more sophisticated implementation, we could parse the AI response
            // to reorder hospitals based on epilepsy specialization

            return array_map(function($hospital) use ($aiAnalysis) {
                $hospital['ai_enhanced'] = true;
                $hospital['emergency_priority'] = $this->calculateEmergencyPriority($hospital, $aiAnalysis);
                return $hospital;
            }, $hospitals);

        } catch (\Exception $e) {
            Log::warning('AI Enhancement Error: ' . $e->getMessage());
            // Return original hospitals if AI enhancement fails
            return $hospitals;
        }
    }

    private function calculateEmergencyPriority($hospital, $aiAnalysis)
    {
        // Simple priority calculation based on hospital type and distance
        $priority = 1;

        if (stripos($hospital['type'], 'تخصصي') !== false) {
            $priority += 2;
        } elseif (stripos($hospital['type'], 'عام') !== false) {
            $priority += 1;
        }

        // Closer hospitals get higher priority
        if ($hospital['distance'] < 2) {
            $priority += 2;
        } elseif ($hospital['distance'] < 5) {
            $priority += 1;
        }

        return min($priority, 5); // Max priority 5
    }

    private function buildAddress($tags)
    {
        $address = '';

        if (isset($tags['addr:street'])) {
            $address .= $tags['addr:street'];
        }

        if (isset($tags['addr:city'])) {
            if ($address) $address .= ', ';
            $address .= $tags['addr:city'];
        }

        if (!$address) {
            $address = 'المملكة العربية السعودية';
        }

        return $address;
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

        // Add common specialties based on tags
        if (isset($tags['emergency']) && $tags['emergency'] === 'yes') {
            $specialties[] = 'طوارئ';
        }

        return array_unique($specialties);
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

        return $earthRadius * $c;
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

    private function buildActivityPrompt(array $deviceData, array $patientHistory = [])
    {
        $eeg = $deviceData['eeg'] ?? [];
        $ecg = $deviceData['ecg'] ?? [];
        $emg = $deviceData['emg'] ?? [];

        $prompt = "بناءً على البيانات التالية من أجهزة المراقبة، حدد النشاط الحالي للمريض:\n\n";

        if (!empty($eeg)) {
            $prompt .= "EEG (دماغ): Alpha: {$eeg['alpha']}, Beta: {$eeg['beta']}, Theta: {$eeg['theta']}, Delta: {$eeg['delta']}\n";
        }

        if (!empty($ecg)) {
            $prompt .= "ECG (قلب): معدل النبض: {$ecg['heart_rate']} نبضة/دقيقة, ضغط الدم: {$ecg['blood_pressure_systolic']}/{$ecg['blood_pressure_diastolic']}\n";
        }

        if (!empty($emg)) {
            $prompt .= "EMG (عضلات): توتر العضلات: {$emg['tension']}%, نشاط الأعصاب: {$emg['nerve_signals']}%\n";
        }

        $prompt .= "\nالأنشطة المحتملة: المشي، الجري، الراحة، النوم، القيادة، العمل، الرياضة، الأكل، التوتر.\n";
        $prompt .= "قدم إجابة قصيرة بالنشاط الأكثر احتمالاً مع شرح مختصر.";

        return $prompt;
    }

    private function buildRiskPredictionPrompt(array $deviceData, array $patientHistory = [])
    {
        $eeg = $deviceData['eeg'] ?? [];
        $ecg = $deviceData['ecg'] ?? [];
        $emg = $deviceData['emg'] ?? [];

        $prompt = "قيم خطر حدوث نوبة صرع وحدد الوقت المتوقع لها بناءً على البيانات التالية:\n\n";

        if (!empty($eeg)) {
            $prompt .= "EEG: Alpha: {$eeg['alpha']}, Beta: {$eeg['beta']}, Theta: {$eeg['theta']}, Delta: {$eeg['delta']}\n";
        }

        if (!empty($ecg)) {
            $prompt .= "ECG: معدل النبض: {$ecg['heart_rate']}, ضغط الدم: {$ecg['blood_pressure_systolic']}/{$ecg['blood_pressure_diastolic']}\n";
        }

        if (!empty($emg)) {
            $prompt .= "EMG: توتر العضلات: {$emg['tension']}%, نشاط الأعصاب: {$emg['nerve_signals']}%\n";
        }

        if (!empty($patientHistory)) {
            $prompt .= "\nالتاريخ الطبي: " . implode(", ", $patientHistory) . "\n";
        }

        $prompt .= "\nقيم الخطر: منخفض (أقل من 30%)، متوسط (30-60%)، عالي (أكثر من 60%).\n";
        $prompt .= "حدد الوقت المتوقع للنوبة (مثل: خلال 15 دقيقة، خلال ساعة، خلال يوم، إلخ).\n";
        $prompt .= "قدم تقييماً مع النسبة المئوية والوقت المتوقع والأسباب.";

        return $prompt;
    }

    private function buildRecommendationsPrompt(array $deviceData, string $riskLevel, array $patientHistory = [])
    {
        $prompt = "بناءً على مستوى الخطر: {$riskLevel}\n";
        $prompt .= "البيانات الحالية:\n";

        foreach ($deviceData as $device => $data) {
            $prompt .= ucfirst($device) . ": " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        }

        $prompt .= "\nقدم 3-5 نصائح طبية مناسبة للمريض في هذه الحالة.";

        return $prompt;
    }

    private function buildHospitalsPrompt($latitude, $longitude, array $deviceData = [])
    {
        $prompt = "الموقع: خطوط العرض {$latitude}, خطوط الطول {$longitude} (الرياض، المملكة العربية السعودية تقريباً)\n\n";
        $prompt .= "قدم قائمة بأقرب 3-5 مستشفيات متخصصة في علاج الصرع مع:\n";
        $prompt .= "- اسم المستشفى\n";
        $prompt .= "- المسافة التقريبية\n";
        $prompt .= "- الوقت المقدر للوصول\n";
        $prompt .= "- التخصصات المتاحة\n\n";
        $prompt .= "ركز على المستشفيات الفعلية في المنطقة.";

        return $prompt;
    }

    private function parseActivityAnalysis(string $analysis)
    {
        // Extract activity from AI response
        $activities = ['walking' => 'المشي', 'running' => 'الجري', 'resting' => 'الراحة',
                      'sleeping' => 'النوم', 'driving' => 'القيادة', 'working' => 'العمل'];

        foreach ($activities as $key => $arabic) {
            if (stripos($analysis, $arabic) !== false || stripos($analysis, $key) !== false) {
                return $key;
            }
        }

        return 'resting'; // fallback
    }

    private function parseRiskPrediction(string $prediction)
    {
        // Extract risk level, probability, and time to event
        $riskLevel = 'low';
        $probability = 0.1;
        $timeToEvent = 'غير محدد';

        if (stripos($prediction, 'عالي') !== false || stripos($prediction, 'high') !== false) {
            $riskLevel = 'high';
            $probability = 0.8;
        } elseif (stripos($prediction, 'متوسط') !== false || stripos($prediction, 'medium') !== false) {
            $riskLevel = 'medium';
            $probability = 0.5;
        }

        // Try to extract percentage
        preg_match('/(\d+)%/', $prediction, $matches);
        if (!empty($matches)) {
            $probability = intval($matches[1]) / 100;
        }

        // Try to extract time to event
        preg_match('/خلال\s+([^.،]+)/u', $prediction, $timeMatches);
        if (!empty($timeMatches)) {
            $timeToEvent = trim($timeMatches[1]);
        }

        return [
            'risk_level' => $riskLevel,
            'probability' => $probability,
            'time_to_event' => $timeToEvent,
            'explanation' => $prediction
        ];
    }

    private function parseRecommendations(string $recommendations)
    {
        // Split recommendations into array
        $lines = explode("\n", $recommendations);
        $cleanRecommendations = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && !preg_match('/^\d+\./', $line)) {
                $cleanRecommendations[] = $line;
            }
        }

        return array_slice($cleanRecommendations, 0, 5); // Max 5 recommendations
    }

    private function parseHospitalsInfo(string $hospitalsInfo)
    {
        // Parse hospitals information
        $lines = explode("\n", $hospitalsInfo);
        $hospitals = [];
        $baseLat = 24.7136; // Riyadh latitude
        $baseLng = 46.6753; // Riyadh longitude

        foreach ($lines as $line) {
            if (preg_match('/مستشفى (.+)/', $line, $matches)) {
                // Add some random offset for different hospitals
                $offset = count($hospitals) * 0.01;
                $hospitals[] = [
                    'name' => trim($matches[1]),
                    'lat' => $baseLat + $offset,
                    'lng' => $baseLng + $offset,
                    'distance' => 'غير محدد',
                    'eta' => 'غير محدد',
                    'address' => 'الرياض، المملكة العربية السعودية'
                ];
            }
        }

        return array_slice($hospitals, 0, 5);
    }

    private function getFallbackActivityAnalysis(array $deviceData)
    {
        // Simple fallback logic
        $heartRate = $deviceData['ecg']['heart_rate'] ?? 70;
        $tension = $deviceData['emg']['tension'] ?? 0;

        if ($heartRate > 120 && $tension > 60) return 'running';
        if ($heartRate > 90 && $tension > 30) return 'walking';
        if ($heartRate < 60) return 'sleeping';

        return 'resting';
    }

    private function getFallbackRiskPrediction(array $deviceData)
    {
        $heartRate = $deviceData['ecg']['heart_rate'] ?? 70;
        $tension = $deviceData['emg']['tension'] ?? 0;

        $probability = 0.1;
        if ($heartRate > 100) $probability += 0.3;
        if ($tension > 80) $probability += 0.2;

        $riskLevel = $probability > 0.7 ? 'high' : ($probability > 0.4 ? 'medium' : 'low');

        return [
            'risk_level' => $riskLevel,
            'probability' => $probability,
            'explanation' => 'تحليل احتياطي بناءً على معدل النبض وتوتر العضلات'
        ];
    }

    private function getFallbackRecommendations(string $riskLevel)
    {
        $recommendations = [
            'low' => [
                'حافظ على نمط حياتك الطبيعي',
                'تناول أدويتك بانتظام',
                'مارس الرياضة الخفيفة'
            ],
            'medium' => [
                'تجنب الإجهاد والتوتر',
                'استرح في مكان هادئ',
                'اتصل بطبيبك إذا استمر الشعور بعدم الراحة'
            ],
            'high' => [
                'اجلس في مكان آمن ومريح',
                'أبلغ شخصاً موثوقاً بجانبك',
                'اتصل بالطوارئ إذا شعرت بأعراض نوبة',
                'تجنب القيادة أو استخدام الآلات'
            ]
        ];

        return $recommendations[$riskLevel] ?? $recommendations['low'];
    }
}