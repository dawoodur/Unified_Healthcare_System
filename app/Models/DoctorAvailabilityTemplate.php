<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one recurring weekly visiting window a doctor holds (e.g.
 * "every Sunday, 9:00-13:00, onsite, up to 15 patients"). This is a
 * TEMPLATE, not a list of actual bookable dates — real upcoming bookable
 * dates are worked out on the fly by AppointmentService, by matching a
 * template's day_of_week against real calendar dates.
 *
 * There's no per-patient time slot — everyone booked into this window for
 * a given date shares the same window and just gets a queue serial number
 * (see AppointmentService::book()), same as how a real doctor's chamber
 * works: patients don't get a personal appointment time, they get a
 * number and wait their turn within the visiting hours.
 */
class DoctorAvailabilityTemplate extends Model
{
    public $timestamps = false;
    protected $table = 'doctor_availability_templates';
    protected $primaryKey = 'template_id';

    protected $fillable = [
        'doctor_id', 'hospital_id', 'day_of_week', 'start_time', 'end_time',
        'mode', 'max_patients', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Sunday=0 ... Saturday=6, matching PHP's own date('w') numbering, so
    // we can compare a template's day_of_week directly against a real
    // date's weekday number without any conversion.
    public const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    /** Every appointment that was booked against this specific template. */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'template_id', 'template_id');
    }
}
