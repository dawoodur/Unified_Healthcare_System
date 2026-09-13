<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one test/operation/procedure recommended within a
 * prescription (e.g. "CBC blood test", "Appendectomy") — the
 * facility-type equivalent of PrescriptionItem, which is medicines only.
 */
class PrescriptionFacilityItem extends Model
{
    public $timestamps = false;
    protected $table = 'prescription_facility_items';
    protected $primaryKey = 'prescription_facility_item_id';

    protected $fillable = ['prescription_id', 'facility_type_id', 'notes'];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id', 'prescription_id');
    }

    public function facilityType(): BelongsTo
    {
        return $this->belongsTo(FacilityType::class, 'facility_type_id', 'facility_type_id');
    }
}
