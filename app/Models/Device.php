<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'status',
        'battery_level',
        'simulation_mode',
        'last_data',
    ];

    protected $appends = [
        'icon_url',
        'is_connected',
        'type_label',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIconUrlAttribute()
    {
        return match($this->type) {
            'eeg' => asset('img/logo.png'),
            'ecg' => asset('img/logo.png'),
            'emg' => asset('img/logo.png'),
            default => asset('img/logo.png'),
        };
    }

    public function getIsConnectedAttribute()
    {
        return $this->status === 'connected';
    }

    public function getTypeLabelAttribute()
    {
        return strtoupper($this->type ?? 'Unknown');
    }

    /**
     * Generate simulated data for this device
     */
    public function generateSimulatedData()
    {
        if (!$this->simulation_mode) {
            return null;
        }

        $data = [];

        switch ($this->type) {
            case 'eeg':
                $data = [
                    'alpha' => rand(5, 15),
                    'beta' => rand(10, 35),
                    'theta' => rand(3, 8),
                    'delta' => rand(1, 4),
                    'activity_level' => rand(0, 1) ? 'normal' : 'abnormal'
                ];
                break;
            case 'ecg':
                $data = [
                    'heart_rate' => rand(60, 140),
                    'blood_pressure_systolic' => rand(90, 160),
                    'blood_pressure_diastolic' => rand(60, 100),
                    'oxygen_saturation' => rand(95, 100)
                ];
                break;
            case 'emg':
                $data = [
                    'tension' => rand(0, 100),
                    'muscle_activity' => rand(0, 100),
                    'nerve_signals' => rand(0, 100)
                ];
                break;
        }

        $this->update(['last_data' => json_encode($data)]);
        return $data;
    }

    /**
     * Get the last data as array
     */
    public function getLastDataAttribute()
    {
        return $this->attributes['last_data'] ? json_decode($this->attributes['last_data'], true) : null;
    }

    /**
     * Check if device is in simulation mode
     */
    public function isInSimulationMode()
    {
        return $this->simulation_mode;
    }
}
