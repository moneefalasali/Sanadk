<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Services\MedicalDeviceService;
use App\Models\User;

class ProcessDeviceDataJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $userId;
    public $payload;
    public $deviceType;

    public function __construct(int $userId, string $deviceType, array $payload)
    {
        $this->userId = $userId;
        $this->deviceType = $deviceType;
        $this->payload = $payload;
    }

    public function handle(MedicalDeviceService $service)
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        switch ($this->deviceType) {
            case 'polar':
                $result = $service->processPolarData($user, $this->payload);
                event(new \App\Events\MedicalDataUpdated($user->id, $result));
                if (isset($result['analysis']) && ($result['analysis']['seizure_detected'] ?? false)) {
                    event(new \App\Events\SeizureAlert($user->id, [
                        'prediction_score' => $result['analysis']['prediction_score'] ?? 1.0,
                        'recommendations' => $result['analysis']['recommendations'] ?? [],
                    ]));
                }
                break;
            case 'emotiv':
                $result = $service->processEmotivEEGData($user, $this->payload);
                event(new \App\Events\MedicalDataUpdated($user->id, $result));
                if (isset($result['analysis']) && (($result['analysis']['seizure_risk'] ?? 'low') === 'high')) {
                    event(new \App\Events\SeizureAlert($user->id, [
                        'risk_score' => $result['analysis']['risk_score'] ?? 0.0,
                        'abnormal_channels' => $result['analysis']['abnormal_channels'] ?? [],
                    ]));
                }
                break;
            case 'esp32':
                $result = $service->processESP32Data($user, $this->payload);
                event(new \App\Events\MedicalDataUpdated($user->id, $result));
                if (isset($result['analysis']) && ($result['analysis']['seizure_detected'] ?? false)) {
                    event(new \App\Events\SeizureAlert($user->id, [
                        'prediction_score' => $result['analysis']['prediction_score'] ?? 1.0,
                        'recommendations' => $result['analysis']['recommendations'] ?? [],
                    ]));
                }
                break;
            default:
                // Unknown device type
                break;
        }
    }
}
