<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one set of vital signs (blood pressure, heart rate,
 * temperature, height/weight) a doctor measured, usually right after
 * marking an appointment visited — see VitalController::store().
 */
class Vital extends Model
{
    public $timestamps = false; // this table tracks its own recorded_at instead
    protected $primaryKey = 'vital_id';

    protected $fillable = [
        'patient_id', 'appointment_id', 'recorded_by_doctor_id',
        'blood_pressure_systolic', 'blood_pressure_diastolic', 'heart_rate',
        'temperature_celsius', 'height_cm', 'weight_kg', 'notes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }

    public function recordedByDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'recorded_by_doctor_id', 'doctor_id');
    }

    /** "120/80" if both numbers were recorded, "—" otherwise. */
    public function bloodPressureLabel(): string
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return "{$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic}";
        }

        return '—';
    }
}
