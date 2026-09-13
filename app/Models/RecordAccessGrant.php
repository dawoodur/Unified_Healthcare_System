<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one doctor's request to view one patient's medical records,
 * with its own approve/deny/expiry lifecycle — a patient can grant (and
 * let expire) access to many different doctors over time, each getting
 * its own row instead of one mutable "is this doctor allowed?" flag, so
 * the history is never lost. Lifecycle: requested -> otp_sent (an email
 * OTP was sent to the PATIENT, purpose 'record_access' — see OtpService)
 * -> approved (patient entered the code) or denied, and approved rows
 * naturally stop being usable once expires_at passes — see isActive().
 */
class RecordAccessGrant extends Model
{
    public $timestamps = false;
    protected $table = 'record_access_grants';
    protected $primaryKey = 'grant_id';

    protected $fillable = ['patient_id', 'doctor_id', 'otp_id', 'status', 'granted_at', 'expires_at'];

    protected $casts = [
        'requested_at' => 'datetime',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function otp(): BelongsTo
    {
        return $this->belongsTo(OtpVerification::class, 'otp_id', 'otp_id');
    }

    /**
     * Is this grant usable RIGHT NOW to view records? Computed from the
     * actual timestamp rather than trusting the stored `status` string,
     * since nothing proactively flips 'approved' rows to 'expired' the
     * instant they lapse (there's no background job in this app) — this
     * is what every real authorization check should call.
     */
    public function isActive(): bool
    {
        return $this->status === 'approved' && $this->expires_at !== null && $this->expires_at->isFuture();
    }

    // Wording lives in lang/en|bn/statuses.php so it switches with locale.
    private const STATUS_BADGE_CLASSES = [
        'requested' => 'badge-warning',
        'otp_sent' => 'badge-warning',
        'approved' => 'badge-success',
        'denied' => 'badge-danger',
        'expired' => 'badge-danger',
    ];

    public function statusLabel(): string
    {
        if ($this->status === 'approved' && !$this->isActive()) {
            return __('statuses.record_access_grant.expired'); // stored status hasn't caught up yet — show the true state
        }

        $key = 'statuses.record_access_grant.' . $this->status;
        return __($key) !== $key ? __($key) : ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        if ($this->status === 'approved' && !$this->isActive()) {
            return 'badge-danger';
        }

        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge';
    }
}
