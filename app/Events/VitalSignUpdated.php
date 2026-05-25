<?php

namespace App\Events;

use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VitalSignUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $vitalSign;
    public $analysis;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, VitalSign $vitalSign, array $analysis)
    {
        $this->user = $user;
        $this->vitalSign = $vitalSign;
        $this->analysis = $analysis;
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
            'vital_sign' => $this->vitalSign,
            'analysis' => $this->analysis,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
