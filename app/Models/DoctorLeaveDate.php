<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row = one date a doctor has blocked off — see AppointmentService for how it's excluded from booking. */
class DoctorLeaveDate extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'leave_id';

    protected $fillable = ['doctor_id', 'leave_date', 'reason'];

    protected $casts = [
        'leave_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }
}
