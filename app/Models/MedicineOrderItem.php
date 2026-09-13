<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one medicine line item within an order (e.g. "3x Napa 500mg").
 * unit_price_snapshot is the price at the moment of ordering — if the
 * pharmacy changes its price later, this line still shows what the
 * patient was actually charged, same idea as hospital_facilities pricing
 * on a booking.
 */
class MedicineOrderItem extends Model
{
    public $timestamps = false;
    protected $table = 'medicine_order_items';
    protected $primaryKey = 'order_item_id';

    protected $fillable = ['order_id', 'medicine_master_id', 'quantity', 'unit_price_snapshot'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MedicineOrder::class, 'order_id', 'order_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(MedicineMaster::class, 'medicine_master_id', 'medicine_master_id');
    }

    public function lineTotal(): float
    {
        return $this->quantity * $this->unit_price_snapshot;
    }
}
