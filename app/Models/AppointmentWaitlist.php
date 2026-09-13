<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row = one patient waiting for a spot to open in a full window — see AppointmentService. */
class AppointmentWaitlist extends Model
{
    public $timestamps = false;
    protected $table = 'appointment_waitlist';
    protected $primaryKey = 'waitlist_id';

    protected $fillable = ['patient_id', 'doctor_id', 'template_id', 'requested_date', 'status', 'notified_at'];

    protected $casts = [
        'requested_date' => 'date',
        'created_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DoctorAvailabilityTemplate::class, 'template_id', 'template_id');
    }
}
