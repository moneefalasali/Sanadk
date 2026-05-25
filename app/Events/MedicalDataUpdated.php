<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MedicalDataUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $patientId;
    public $payload;

    public function __construct(int $patientId, array $payload)
    {
        $this->patientId = $patientId;
        $this->payload = $payload;
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
            'data' => $this->payload,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
