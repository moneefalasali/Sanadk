<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Services\MedicalDeviceService;
use App\Models\User;

class ProcessDeviceDisconnectionJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $userId;
    public $deviceType;
    public $deviceId;

    public function __construct(int $userId, string $deviceType, string $deviceId)
    {
        $this->userId = $userId;
        $this->deviceType = $deviceType;
        $this->deviceId = $deviceId;
    }

    public function handle(MedicalDeviceService $service)
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $result = $service->handleDeviceDisconnection($user, $this->deviceType, $this->deviceId);

        // Broadcast device disconnected event via queue
        event(new \App\Events\DeviceDisconnected($user, $this->deviceType, $this->deviceId));
    }
}
