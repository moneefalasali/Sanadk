<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceDisconnected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $deviceType;
    public $deviceId;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, $deviceType, $deviceId)
    {
        $this->user = $user;
        $this->deviceType = $deviceType;
        $this->deviceId = $deviceId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
            new PrivateChannel('doctor.patient.' . $this->user->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'device_type' => $this->deviceType,
            'device_id' => $this->deviceId,
            'message' => "Device {$this->deviceType} ({$this->deviceId}) disconnected",
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
