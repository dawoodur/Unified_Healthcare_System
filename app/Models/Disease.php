<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One row = one condition the symptom checker can suggest. See SymptomCheckerController for the rule-based keyword matching. */
class Disease extends Model
{
    public $timestamps = false;
    protected $table = 'diseases';
    protected $primaryKey = 'disease_id';

    protected $fillable = ['name', 'advice'];

    public function keywords(): HasMany
    {
        return $this->hasMany(DiseaseKeyword::class, 'disease_id', 'disease_id');
    }
}
