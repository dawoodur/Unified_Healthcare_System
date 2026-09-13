<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one specific facility a hospital could offer (e.g. "Complete
 * Blood Count (CBC)", "ICU Bed", "MRI") — the shared, platform-wide list
 * every hospital picks from, seeded once in DatabaseSeeder. A hospital's
 * actual price for one of these lives in HospitalFacility instead.
 */
class FacilityType extends Model
{
    public $timestamps = false;
    protected $table = 'facility_types';
    protected $primaryKey = 'facility_type_id';

    protected $fillable = ['category_id', 'name', 'unit_label', 'is_occupancy'];

    protected $casts = [
        'is_occupancy' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FacilityCategory::class, 'category_id', 'category_id');
    }

    /** Every hospital's price offering for this facility type. */
    public function hospitalOfferings(): HasMany
    {
        return $this->hasMany(HospitalFacility::class, 'facility_type_id', 'facility_type_id');
    }
}
