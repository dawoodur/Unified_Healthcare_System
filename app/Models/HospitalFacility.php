<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one hospital's price (and daily booking quota) for one facility
 * type (e.g. "Dhaka Central Hospital charges BDT 800 for an MRI, up to 10 a
 * day"). This table powers both the price comparison feature — see
 * FacilityComparisonController — and the actual booking feature — see
 * FacilityBookingService — since a patient books against a specific
 * hospital's offering of a facility type, not the facility type in general.
 */
class HospitalFacility extends Model
{
    public $timestamps = false; // this table only tracks updated_at, handled by the database itself
    protected $table = 'hospital_facilities';
    protected $primaryKey = 'facility_offering_id';

    protected $fillable = ['hospital_id', 'facility_type_id', 'price', 'daily_capacity'];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    public function facilityType(): BelongsTo
    {
        return $this->belongsTo(FacilityType::class, 'facility_type_id', 'facility_type_id');
    }

    /**
     * Every booking made against this hospital's offering of this facility
     * type. Note this joins on (hospital_id, facility_type_id), not a
     * foreign key to this row's own ID — facility_bookings doesn't store
     * facility_offering_id, since a hospital could in theory delete and
     * re-add its price listing without that invalidating past bookings.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(FacilityBooking::class, 'hospital_id', 'hospital_id')
            ->where('facility_type_id', $this->facility_type_id);
    }
}
