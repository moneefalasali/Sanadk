<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Services\MedicalDeviceService;
use App\Models\User;

class ProcessHeartRateJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $userId;
    public $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, array $payload)
    {
        $this->userId = $userId;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(MedicalDeviceService $service)
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        // Process and get result (service no longer broadcasts)
        $result = $service->processPolarData($user, $this->payload);

        // Broadcast via event after processing
        event(new \App\Events\MedicalDataUpdated($user->id, $result));

        // If analysis indicates seizure, emit SeizureAlert
        if (isset($result['analysis']) && ($result['analysis']['seizure_detected'] ?? false)) {
            event(new \App\Events\SeizureAlert($user->id, [
                'prediction_score' => $result['analysis']['prediction_score'] ?? 1.0,
                'recommendations' => $result['analysis']['recommendations'] ?? [],
            ]));
        }
    }
}
