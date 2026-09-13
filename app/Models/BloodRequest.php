<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one hospital's emergency call for a specific blood type,
 * emailed to every eligible donor of that type at the moment it was sent
 * — see BloodDonationService::sendEmergencyRequest().
 */
class BloodRequest extends Model
{
    public $timestamps = false; // this table only tracks created_at, not updated_at
    protected $table = 'blood_requests';
    protected $primaryKey = 'blood_request_id';

    protected $fillable = ['hospital_id', 'blood_group', 'message', 'recipient_count'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }
}
