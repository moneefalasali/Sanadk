<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Services\MedicalDeviceService;
use App\Models\User;

class ProcessEEGJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $userId;
    public $payload;

    public function __construct(int $userId, array $payload)
    {
        $this->userId = $userId;
        $this->payload = $payload;
    }

    public function handle(MedicalDeviceService $service)
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $result = $service->processEmotivEEGData($user, $this->payload);

        // Broadcast processed EEG data
        event(new \App\Events\MedicalDataUpdated($user->id, $result));

        // If EEG analysis shows high seizure risk, emit SeizureAlert
        if (isset($result['analysis']) && (($result['analysis']['seizure_risk'] ?? 'low') === 'high')) {
            event(new \App\Events\SeizureAlert($user->id, [
                'risk_score' => $result['analysis']['risk_score'] ?? 0.0,
                'abnormal_channels' => $result['analysis']['abnormal_channels'] ?? [],
            ]));
        }
    }
}
