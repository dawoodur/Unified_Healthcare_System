<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one patient's 1-5 rating (+ optional comment) of ONE doctor,
 * hospital, pharmacy, OR delivery agent — exactly one of those four
 * columns is set, enforced by a database CHECK constraint (see the
 * reviews migrations), not just app logic. A patient can only ever have
 * ONE review per target (also a database-level rule, via unique indexes)
 * — submitting again edits the existing one instead of adding a second;
 * see ReviewController::store().
 *
 * IMPORTANT: nothing in this model exposes which patient wrote a review
 * to anyone but the patient themselves — ReviewController's
 * provider-facing methods (forDoctor/forHospital/forPharmacy/forDelivery)
 * deliberately select only rating/comment/created_at, never patient_id,
 * so a doctor/hospital/pharmacy/delivery man can read feedback without
 * ever learning who wrote it.
 */
class Review extends Model
{
    public $timestamps = false; // this table only tracks created_at, not updated_at
    protected $primaryKey = 'review_id';

    protected $fillable = [
        'patient_id', 'doctor_id', 'hospital_id', 'pharmacy_id', 'delivery_agent_id',
        'appointment_id', 'order_id', 'rating', 'comment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id', 'pharmacy_id');
    }

    public function deliveryAgent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id', 'delivery_agent_id');
    }
}
