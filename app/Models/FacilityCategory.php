<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one broad grouping of facility types (e.g. "Diagnostic Test",
 * "Surgery", "Critical Care") — used just to organize the facility_types
 * list in the UI, seeded once in DatabaseSeeder.
 */
class FacilityCategory extends Model
{
    public $timestamps = false;
    protected $table = 'facility_categories';
    protected $primaryKey = 'category_id';

    protected $fillable = ['category_name'];

    public function facilityTypes(): HasMany
    {
        return $this->hasMany(FacilityType::class, 'category_id', 'category_id');
    }
}
