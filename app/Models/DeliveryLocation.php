<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one position reading from a delivery agent's device while they
 * were carrying one order. Written only while that order is out_for_delivery.
 *
 * No created_at/updated_at: a reading is a single moment, and recorded_at IS
 * that moment (several other tables in this app do the same — see
 * DeliveryAgent.php). Latitude/longitude are cast to float so distance maths
 * can use them directly; they are stored as decimal to avoid drift.
 */
class DeliveryLocation extends Model
{
    public $timestamps = false;
    protected $table = 'delivery_locations';
    protected $primaryKey = 'location_id';

    protected $fillable = [
        'order_id', 'latitude', 'longitude', 'accuracy_m', 'source', 'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy_m' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MedicineOrder::class, 'order_id', 'order_id');
    }

    /** True when this reading came from the demo simulator, not a real device. */
    public function isSimulated(): bool
    {
        return $this->source === 'simulated';
    }
}
