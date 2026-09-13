<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one medicine within a prescription (e.g. "Napa 500mg, 1 tablet,
 * twice daily, 5 days"). dosage/frequency/duration_days are free-text-ish
 * instructions for the PATIENT to follow — they're not an order quantity,
 * so ordering that medicine still asks the patient how many units they want
 * (see CartController::add()).
 */
class PrescriptionItem extends Model
{
    public $timestamps = false;
    protected $table = 'prescription_items';
    protected $primaryKey = 'prescription_item_id';

    protected $fillable = ['prescription_id', 'medicine_master_id', 'for_illness', 'dosage', 'frequency', 'duration_days', 'notes', 'refill_reminder_sent_at'];

    protected $casts = [
        'refill_reminder_sent_at' => 'datetime',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id', 'prescription_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(MedicineMaster::class, 'medicine_master_id', 'medicine_master_id');
    }

    public function reminderTimes(): HasMany
    {
        return $this->hasMany(MedicineReminderTime::class, 'prescription_item_id', 'prescription_item_id');
    }

    /**
     * Is this item still within its dosing course right now? An
     * open-ended item (no duration_days set) is always considered
     * active — there's no end date to compare against. Needs the
     * `prescription` relation eager-loaded.
     */
    public function isDosingActive(): bool
    {
        if (!$this->duration_days) {
            return true;
        }

        $finishDate = $this->prescription->issued_at->copy()->addDays($this->duration_days);
        return now()->lte($finishDate);
    }
}
