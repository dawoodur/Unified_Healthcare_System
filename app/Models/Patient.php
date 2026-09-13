<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One row = one patient's profile details (name, age, blood group, reward
 * points). Linked to an Account row (the login/email/password) by
 * account_id — see Account.php for how that connection works.
 */
class Patient extends Model
{
    use HasFactory;

    // The `patients` table has no created_at/updated_at columns, so tell
    // Eloquent not to expect or auto-manage them.
    public $timestamps = false;

    // Our ID column is called "patient_id", not Eloquent's default "id".
    protected $primaryKey = 'patient_id';

    // The only fields allowed to be set via Patient::create([...]).
    protected $fillable = ['account_id', 'full_name', 'age', 'blood_group', 'gender', 'address', 'reward_points_balance', 'last_donated_at'];

    protected $casts = [
        'last_donated_at' => 'date',
    ];

    /**
     * The reverse of Account's patient() method: given a Patient, find the
     * one Account row it belongs to. $patient->account gives you back an
     * Account object (email, password, etc.) for this patient.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    /** Every appointment this patient has ever booked, any doctor, any status. */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id', 'patient_id');
    }

    /** Every hospital facility booking this patient has ever made (MRI, ICU bed, etc.), any status. */
    public function facilityBookings(): HasMany
    {
        return $this->hasMany(FacilityBooking::class, 'patient_id', 'patient_id');
    }

    /** Every medicine order this patient has ever placed, any pharmacy, any status. */
    public function medicineOrders(): HasMany
    {
        return $this->hasMany(MedicineOrder::class, 'patient_id', 'patient_id');
    }

    /** Every prescription a doctor has ever issued for this patient. */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'patient_id', 'patient_id');
    }

    /**
     * Every medicine_master_id this patient has EVER been prescribed, across
     * every prescription — this is exactly the set CartController::add()
     * checks against before letting a medicine into the cart. A flat list
     * of IDs (not a query builder) since every caller just needs
     * "is X in this list?", and this patient won't have thousands of
     * prescriptions to make that wasteful.
     */
    public function prescribedMedicineIds(): array
    {
        return PrescriptionItem::whereIn('prescription_id', $this->prescriptions()->pluck('prescription_id'))
            ->pluck('medicine_master_id')
            ->unique()
            ->values()
            ->all();
    }

    /** Every item in this patient's medical history — prescriptions, lab results, notes, uploads. */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'patient_id', 'patient_id');
    }

    /** Every known allergy this patient has on file, self-reported or doctor-noted. */
    public function allergies(): HasMany
    {
        return $this->hasMany(Allergy::class, 'patient_id', 'patient_id');
    }

    /** Every vital-signs reading a doctor has ever recorded for this patient. */
    public function vitals(): HasMany
    {
        return $this->hasMany(Vital::class, 'patient_id', 'patient_id');
    }

    /** Patient-maintained longer-lived health context (diet, therapy, risks, etc.). */
    public function healthProfile(): HasOne
    {
        return $this->hasOne(PatientHealthProfile::class, 'patient_id', 'patient_id');
    }

    /** Every doctor's request (past or present) to view this patient's records. */
    public function recordAccessGrants(): HasMany
    {
        return $this->hasMany(RecordAccessGrant::class, 'patient_id', 'patient_id');
    }

    /** This patient's full reward-point history (earned + spent) — reward_points_balance is just a cached total of these. */
    public function rewardPointLedger(): HasMany
    {
        return $this->hasMany(RewardPointLedger::class, 'patient_id', 'patient_id');
    }

    /** Every major-operation request this patient has ever made, any hospital, any status. */
    public function operationRequests(): HasMany
    {
        return $this->hasMany(OperationRequest::class, 'patient_id', 'patient_id');
    }

    /** This patient's full blood donation history — last_donated_at is just a cached copy of the most recent one. */
    public function bloodDonations(): HasMany
    {
        return $this->hasMany(BloodDonation::class, 'patient_id', 'patient_id');
    }

    /** The doctors this patient has bookmarked for faster re-booking — see FavoriteDoctor.php. */
    public function favoriteDoctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'favorite_doctors', 'patient_id', 'doctor_id');
    }
}
