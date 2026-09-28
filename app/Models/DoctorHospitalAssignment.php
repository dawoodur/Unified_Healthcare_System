<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one hospital saying "this doctor works here." Created ONLY by
 * a hospital (see HospitalDoctorController::assign()) — a doctor cannot
 * add themselves to a hospital's staff list, only accept/exist within an
 * assignment the hospital itself created.
 */
class DoctorHospitalAssignment extends Model
{
    public $timestamps = false;
    protected $table = 'doctor_hospital_assignments';
    protected $primaryKey = 'assignment_id';

    protected $fillable = ['doctor_id', 'hospital_id', 'status', 'consultation_fee_override'];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }
}
