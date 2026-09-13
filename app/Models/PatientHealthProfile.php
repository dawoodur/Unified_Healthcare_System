<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Patient-maintained health context that does not belong in a single
 * appointment/vital reading. Clinical measurements such as blood pressure
 * remain in vitals, while this profile stores longer-lived context such as
 * cholesterol notes, major risks, diet, therapy and chronic conditions.
 */
class PatientHealthProfile extends Model
{
    protected $primaryKey = 'health_profile_id';

    protected $fillable = [
        'patient_id',
        'cholesterol_status',
        'diabetes_risk',
        'diet_notes',
        'therapy_notes',
        'major_health_risks',
        'chronic_conditions',
        'lifestyle_notes',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}
