<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeizureAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $patientId;
    public $alertDetails;

    public function __construct(int $patientId, array $alertDetails)
    {
        $this->patientId = $patientId;
        $this->alertDetails = $alertDetails;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('patient.' . $this->patientId),
            new PrivateChannel('doctor.patient.' . $this->patientId),
            new PrivateChannel('family.' . $this->patientId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'alert' => $this->alertDetails,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
