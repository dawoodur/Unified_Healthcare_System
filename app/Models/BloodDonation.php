<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one patient self-reporting that they donated blood on a given
 * date — see BloodDonationService::recordDonation() and the migration's
 * doc comment for how this relates to patients.last_donated_at.
 */
class BloodDonation extends Model
{
    public $timestamps = false; // this table only tracks created_at, not updated_at
    protected $primaryKey = 'donation_id';

    protected $fillable = ['patient_id', 'hospital_id', 'donated_at', 'status', 'confirmed_at', 'reject_reason'];

    protected $casts = [
        'donated_at' => 'date',
        'created_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    /** Waiting for the hospital to confirm it really happened. */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * When the patient may donate again — three days from the moment the
     * hospital confirmed, which is what the countdown on the patient's page
     * runs down to. Null for anything not confirmed.
     */
    public function cooldownEndsAt(): ?\Illuminate\Support\Carbon
    {
        return $this->isConfirmed() && $this->confirmed_at
            ? $this->confirmed_at->copy()->addDays(BloodDonationCooldown::DAYS)
            : null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'confirmed' => 'Confirmed',
            'rejected' => 'Not confirmed',
            default => 'Waiting for hospital',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'confirmed' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
            default => 'text-bg-warning',
        };
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }
}
