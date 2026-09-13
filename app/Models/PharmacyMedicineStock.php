<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one batch of one medicine held by one pharmacy — its own price,
 * expiry date, and quantity. A pharmacy can (and will, over time) have
 * several of these for the same medicine as it restocks — that's why this
 * is its own table instead of columns on MedicineMaster or Pharmacy.
 */
class PharmacyMedicineStock extends Model
{
    public $timestamps = false;
    protected $table = 'pharmacy_medicine_stock';
    protected $primaryKey = 'stock_id';

    protected $fillable = ['pharmacy_id', 'medicine_master_id', 'batch_no', 'expiry_date', 'unit_price', 'quantity_available'];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    /** $stock->pharmacy gives back which Pharmacy holds this batch. */
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id', 'pharmacy_id');
    }

    /** $stock->medicine gives back which MedicineMaster entry this batch is of. */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(MedicineMaster::class, 'medicine_master_id', 'medicine_master_id');
    }

    /**
     * Is this batch's expiry date already in the past? A pharmacist can
     * remove it manually any time (PharmacyInventoryController::destroy())
     * — an expired row shows at the top of pharmacy/inventory.blade.php
     * (sorted by expiry_date) well before this flag would matter. The
     * daily `medicines:remove-expired` scheduled command is the backstop:
     * it sweeps up anything still sitting expired and removes it for
     * real, notifying the pharmacy of exactly what was pulled — see
     * app/Console/Commands/RemoveExpiredMedicineCommand.php.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }
}
