<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row = one daily "take your medicine" alarm time for one prescribed item. */
class MedicineReminderTime extends Model
{
    public $timestamps = false;
    protected $table = 'medicine_reminder_times';
    protected $primaryKey = 'reminder_time_id';

    protected $fillable = ['prescription_item_id', 'reminder_time', 'last_sent_at'];

    protected $casts = [
        'last_sent_at' => 'datetime',
    ];

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class, 'prescription_item_id', 'prescription_item_id');
    }
}
