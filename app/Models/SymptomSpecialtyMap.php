<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one keyword that hints at one specialty, with how strong a
 * signal it is (weight). The rule-based symptom checker
 * (SymptomCheckerController) sums weights per specialty across every
 * keyword found in what a patient typed, and ranks specialties by that
 * total — no AI/ML involved, just keyword matching.
 */
class SymptomSpecialtyMap extends Model
{
    public $timestamps = false;
    protected $table = 'symptom_specialty_map';
    protected $primaryKey = 'map_id';

    protected $fillable = ['keyword', 'specialty_id', 'weight'];

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'specialty_id', 'specialty_id');
    }
}
