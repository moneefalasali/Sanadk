<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $patientId;
    public $status;

    public function __construct(int $patientId, array $status)
    {
        $this->patientId = $patientId;
        $this->status = $status;
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
            'status' => $this->status,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
