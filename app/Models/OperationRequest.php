<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One row = one patient's request for a major operation at one hospital,
 * moving through requested -> offered -> accepted -> completed (or
 * declined/cancelled along the way) — see the migration's doc comment for
 * the full lifecycle, and OperationRequestService for what drives each
 * transition.
 */
class OperationRequest extends Model
{
    public $timestamps = false; // this table tracks created_at/offered_at/responded_at/completed_at itself
    protected $primaryKey = 'operation_request_id';

    protected $fillable = [
        'patient_id', 'hospital_id', 'facility_type_id', 'patient_notes',
        'assigned_doctor_id', 'scheduled_date', 'scheduled_time', 'serial_number', 'price',
        'status', 'offered_at', 'responded_at', 'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'created_at' => 'datetime',
        'offered_at' => 'datetime',
        'responded_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function assignedDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'assigned_doctor_id', 'doctor_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'operation_request_id', 'operation_request_id');
    }

    // Same idea as FacilityBooking::statusLabel() — one place to keep the
    // word/color shown to users consistent. Wording lives in
    // lang/en|bn/statuses.php so it switches with locale.
    private const STATUS_BADGE_CLASSES = [
        'requested' => 'badge-warning',
        'offered' => 'badge-warning',
        'accepted' => 'badge-success',
        'declined' => 'badge-danger',
        'cancelled' => 'badge-danger',
        'completed' => 'badge-success',
    ];

    public function statusLabel(): string
    {
        $key = 'statuses.operation_request.' . $this->status;
        return __($key) !== $key ? __($key) : ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge';
    }
}
