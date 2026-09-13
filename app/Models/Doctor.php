<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one doctor's profile details (name, age, fee, verification
 * status). Linked to an Account row (login/email/password) by account_id.
 */
class Doctor extends Model
{
    use HasFactory;

    // The `doctors` table has no created_at/updated_at columns.
    public $timestamps = false;

    // Our ID column is called "doctor_id", not Eloquent's default "id".
    protected $primaryKey = 'doctor_id';

    // The only fields allowed to be set via Doctor::create([...]).
    protected $fillable = ['account_id', 'full_name', 'age', 'blood_group', 'gender', 'consultation_fee', 'bio', 'verification_status'];

    /**
     * Given a Doctor, find the one Account row it belongs to (email,
     * password, etc). $doctor->account gives back that Account object.
     * "belongsTo" = the foreign key (account_id) lives on THIS table.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    /**
     * A doctor can have several specialties, and a specialty can belong to
     * several doctors — neither side can hold a single foreign key for the
     * other, so there's a third table in between (`doctor_specialties`,
     * just pairs of IDs) that makes the connection. $doctor->specialties
     * gives back a LIST of Specialty objects, not just one.
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'doctor_specialties', 'doctor_id', 'specialty_id');
    }

    /**
     * One doctor can have several certificate uploads over time (e.g. if
     * one gets rejected and they upload a new one). $doctor->certificates
     * gives back a list, ordered however the database returns them.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(DoctorCertificate::class, 'doctor_id', 'doctor_id');
    }

    /** This doctor's weekly recurring availability blocks — see DoctorAvailabilityTemplate.php. */
    public function availabilityTemplates(): HasMany
    {
        return $this->hasMany(DoctorAvailabilityTemplate::class, 'doctor_id', 'doctor_id');
    }

    /** Specific calendar dates this doctor has blocked off — see DoctorLeaveDate.php. */
    public function leaveDates(): HasMany
    {
        return $this->hasMany(DoctorLeaveDate::class, 'doctor_id', 'doctor_id');
    }

    /** Every appointment ever booked with this doctor, any patient, any status. */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id', 'doctor_id');
    }

    /** Every prescription this doctor has ever issued. */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'doctor_id', 'doctor_id');
    }

    /** Every hospital-assignment row for this doctor, active or revoked — see DoctorHospitalAssignment.php. */
    public function hospitalAssignments(): HasMany
    {
        return $this->hasMany(DoctorHospitalAssignment::class, 'doctor_id', 'doctor_id');
    }

    /** Just the hospitals this doctor is CURRENTLY (actively) assigned to. */
    public function activeHospitals(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class, 'doctor_hospital_assignments', 'doctor_id', 'hospital_id')
            ->wherePivot('status', 'active');
    }

    /** Every request this doctor has ever made to view a patient's medical records, any status. */
    public function recordAccessGrants(): HasMany
    {
        return $this->hasMany(RecordAccessGrant::class, 'doctor_id', 'doctor_id');
    }

    /** Every rating/comment a patient has ever left for this doctor — see Review.php. */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'doctor_id', 'doctor_id');
    }

    /** Every operation request this doctor has ever been assigned to. */
    public function assignedOperationRequests(): HasMany
    {
        return $this->hasMany(OperationRequest::class, 'assigned_doctor_id', 'doctor_id');
    }
}
