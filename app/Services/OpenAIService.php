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
     * Search nearby hospitals (alias for findNearbyHospitals)
     */
    public function searchNearbyHospitals($latitude, $longitude, $location = null)
    {
        return $this->findNearbyHospitals($latitude, $longitude);
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

    private function getFallbackHospitals($latitude, $longitude)
    {
        return [
            [
                'name' => 'مستشفى الملك فيصل التخصصي',
                'distance' => '2.5 كم',
                'eta' => '8 دقائق',
                'address' => 'الرياض، المملكة العربية السعودية'
            ],
            [
                'name' => 'مستشفى الحرس الوطني',
                'distance' => '3.8 كم',
                'eta' => '12 دقيقة',
                'address' => 'الرياض، المملكة العربية السعودية'
            ],
            [
                'name' => 'مدينة الملك عبدالعزيز الطبية',
                'distance' => '5.2 كم',
                'eta' => '15 دقيقة',
                'address' => 'الرياض، المملكة العربية السعودية'
            ]
        ];
    }
}