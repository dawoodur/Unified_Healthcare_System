<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one patient self-reporting that they donated blood on a given
 * date — see BloodDonationService::recordDonation() and the migration's
 * doc comment for how this relates to patients.last_donated_at.
 */
class BloodDonation extends Model
{
    public $timestamps = false; // this table only tracks created_at, not updated_at
    protected $primaryKey = 'donation_id';

    protected $fillable = ['patient_id', 'hospital_id', 'donated_at'];

    protected $casts = [
        'donated_at' => 'date',
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
}
