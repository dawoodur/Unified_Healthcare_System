<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One row = one patient's booking for a hospital facility (an MRI, an ICU
 * bed, a blood test, etc.) on a given date. Same idea as Appointment.php:
 * no personal time slot, just a queue serial number for that
 * hospital+facility+date (see FacilityBookingService::book()).
 */
class FacilityBooking extends Model
{
    public $timestamps = false; // this table only has created_at, not updated_at
    protected $primaryKey = 'facility_booking_id';

    protected $fillable = [
        'patient_id', 'hospital_id', 'facility_type_id',
        'booking_date', 'requested_days', 'serial_number', 'price', 'status',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    public function facilityType(): BelongsTo
    {
        return $this->belongsTo(FacilityType::class, 'facility_type_id', 'facility_type_id');
    }

    // Same reasoning as Appointment::statusLabel() — one place to keep the
    // word shown to users consistent, even though it's a smaller set of
    // statuses here (no "no_show" or "confirmed" concept for a facility).
    // Wording lives in lang/en|bn/statuses.php so it switches with locale.
    private const STATUS_BADGE_CLASSES = [
        'booked' => 'badge-warning',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger',
    ];

    public function statusLabel(): string
    {
        $key = 'statuses.facility_booking.' . $this->status;
        return __($key) !== $key ? __($key) : ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge';
    }

    /** The date the patient expects to leave by, if they gave a requested_days (occupancy bookings only). */
    public function expectedDischargeDate(): ?Carbon
    {
        if (! $this->requested_days) {
            return null;
        }

        return $this->booking_date->copy()->addDays($this->requested_days);
    }
}
