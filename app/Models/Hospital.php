<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one hospital's profile details (name, registration number,
 * address). Linked to an Account row (login/email/password) by account_id.
 * Same shape as Patient.php / Doctor.php — see those files for a fuller
 * explanation of each piece if this one looks unfamiliar.
 */
class Hospital extends Model
{
    use HasFactory;

    public $timestamps = false;          // no created_at/updated_at columns on this table
    protected $primaryKey = 'hospital_id'; // our ID column's real name

    protected $fillable = ['account_id', 'hospital_name', 'registration_number', 'address', 'city'];

    /** $hospital->account gives back the linked Account (email, password, etc). */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    /** Every doctor-assignment row this hospital has ever created, active or revoked. */
    public function doctorAssignments(): HasMany
    {
        return $this->hasMany(DoctorHospitalAssignment::class, 'hospital_id', 'hospital_id');
    }

    /** Just the doctors CURRENTLY (actively) assigned to this hospital. */
    public function activeDoctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_hospital_assignments', 'hospital_id', 'doctor_id')
            ->wherePivot('status', 'active');
    }

    /** This hospital's priced facility offerings — see HospitalFacility.php. */
    public function facilities(): HasMany
    {
        return $this->hasMany(HospitalFacility::class, 'hospital_id', 'hospital_id');
    }

    /** Every patient booking made against ANY of this hospital's facilities, any status. */
    public function facilityBookings(): HasMany
    {
        return $this->hasMany(FacilityBooking::class, 'hospital_id', 'hospital_id');
    }

    /** Every onsite appointment ever booked at this hospital, any doctor, any status. */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'hospital_id', 'hospital_id');
    }

    /** Every rating/comment a patient has ever left for this hospital — see Review.php. */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'hospital_id', 'hospital_id');
    }

    /** Every major-operation request made to this hospital, any patient, any status. */
    public function operationRequests(): HasMany
    {
        return $this->hasMany(OperationRequest::class, 'hospital_id', 'hospital_id');
    }

    /** Every emergency blood request this hospital has ever sent out. */
    public function bloodRequests(): HasMany
    {
        return $this->hasMany(BloodRequest::class, 'hospital_id', 'hospital_id');
    }

    /**
     * Which payment methods (bKash, Cash, Card, Bank Transfer) this
     * hospital currently accepts, plus its own account_details for each
     * (e.g. a bKash merchant number) — see hospital_payment_methods.
     * withPivot() is what makes $method->pivot->account_details reachable
     * on each result, not just the payment method's own name.
     */
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class, 'hospital_payment_methods', 'hospital_id', 'payment_method_id')
            ->withPivot('account_details');
    }

    /**
     * "Panthapath, Dhaka" — address and city joined together, so
     * patient-facing pages can show exactly where to go for a facility
     * booking instead of just the city. Skips whichever part is blank, and
     * skips city entirely if it's already mentioned inside the address
     * (hospitals often type the city as part of their street address),
     * so this doesn't print "Panthapath, Dhaka, Dhaka".
     */
    public function fullAddress(): string
    {
        $parts = array_filter([$this->address, $this->city]);

        if ($this->address && $this->city && stripos($this->address, $this->city) !== false) {
            $parts = [$this->address];
        }

        return $parts ? implode(', ', $parts) : '—';
    }
}
