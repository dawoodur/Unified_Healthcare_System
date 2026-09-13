<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one medicine in the platform-wide catalog (e.g. "Paracetamol",
 * brand "Napa", 500mg tablet). This is the shared list every pharmacy picks
 * from — an individual pharmacy's actual stock/price/expiry lives in
 * PharmacyMedicineStock instead (a pharmacy can hold several batches of the
 * same medicine at different prices/expiry dates).
 */
class MedicineMaster extends Model
{
    public $timestamps = false;
    protected $table = 'medicine_master';
    protected $primaryKey = 'medicine_master_id';

    protected $fillable = ['generic_name', 'brand_name', 'form', 'strength'];

    /** $medicine->stock gives back every pharmacy's batches of this medicine. */
    public function stock(): HasMany
    {
        return $this->hasMany(PharmacyMedicineStock::class, 'medicine_master_id', 'medicine_master_id');
    }
}
