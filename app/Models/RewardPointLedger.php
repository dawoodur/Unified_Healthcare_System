<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one reward-point event for one patient — a positive number
 * for earning points (source_type 'review'), a negative number for
 * spending them (source_type 'redemption'), or an admin manually
 * adjusting a balance. This table is the permanent, append-only history;
 * `patients.reward_points_balance` is just a running-total CACHE of it,
 * kept in sync by RewardPointService every time a row is added here —
 * never updated directly anywhere else.
 */
class RewardPointLedger extends Model
{
    public $timestamps = false; // this table only tracks created_at, not updated_at
    protected $table = 'reward_point_ledger';
    protected $primaryKey = 'ledger_id';

    protected $fillable = ['patient_id', 'points', 'source_type', 'source_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}
