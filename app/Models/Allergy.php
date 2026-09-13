<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one known allergy for one patient (e.g. "Penicillin" ->
 * "rash"). Shown prominently wherever a patient's records are viewed —
 * including on the e-prescription form, as a safety check before a
 * doctor prescribes something.
 */
class Allergy extends Model
{
    public $timestamps = false; // this table only has created_at, not updated_at
    protected $primaryKey = 'allergy_id';

    protected $fillable = ['patient_id', 'allergen', 'reaction', 'recorded_by_account_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'recorded_by_account_id', 'account_id');
    }
}
