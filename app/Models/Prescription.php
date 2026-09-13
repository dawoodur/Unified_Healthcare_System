<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one prescription a doctor issued for one appointment (the
 * appointment_id is UNIQUE — a doctor writes at most one prescription per
 * visit, covering however many medicines were needed that visit — see
 * PrescriptionItem.php for the individual medicines). This is what a
 * patient's medicine ordering is gated on: see CartController::add(),
 * which only allows adding a medicine to the cart if it appears in one of
 * the patient's own prescriptions.
 */
class Prescription extends Model
{
    public $timestamps = false; // only issued_at, set by the database's own default
    protected $primaryKey = 'prescription_id';

    protected $fillable = ['appointment_id', 'doctor_id', 'patient_id', 'diagnosis_notes'];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'prescription_id', 'prescription_id');
    }

    /** Tests/operations/procedures recommended alongside the medicines — see PrescriptionFacilityItem.php. */
    public function facilityItems(): HasMany
    {
        return $this->hasMany(PrescriptionFacilityItem::class, 'prescription_id', 'prescription_id');
    }
}
