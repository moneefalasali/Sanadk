<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'doctor_id',
        'report_type',
        'summary',
        'vital_signs_data',
        'eeg_data',
        'seizure_events',
        'recommendations',
        'pdf_path',
        'generated_at',
    ];

    protected $casts = [
        'vital_signs_data' => 'array',
        'eeg_data' => 'array',
        'seizure_events' => 'array',
        'recommendations' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the report
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the doctor that created the report
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
