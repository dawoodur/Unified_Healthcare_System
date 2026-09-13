<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** "Dr. X is currently serving #N on date D" — see AppointmentController::updateQueueStatus(). */
class QueueStatus extends Model
{
    public $timestamps = false; // updated_at is DB-managed (useCurrentOnUpdate), created_at doesn't exist
    protected $table = 'queue_statuses';
    protected $primaryKey = 'queue_status_id';

    protected $fillable = ['doctor_id', 'queue_date', 'current_serial'];

    protected $casts = [
        'queue_date' => 'date',
        'updated_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }
}
