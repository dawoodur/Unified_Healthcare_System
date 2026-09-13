<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One row = one booked appointment. serial_number is the queue number the
 * patient is given for that doctor on that specific date (see
 * AppointmentService::book() for how it's generated safely).
 */
class Appointment extends Model
{
    public $timestamps = false; // this table only has created_at, not updated_at
    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'patient_id', 'doctor_id', 'hospital_id', 'template_id',
        'appointment_type', 'appointment_date', 'appointment_time',
        'serial_number', 'status', 'reminder_sent_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'created_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DoctorAvailabilityTemplate::class, 'template_id', 'template_id');
    }

    /** The video-call room for this appointment, if it's an online one and someone has opened it. */
    public function consultationSession(): HasOne
    {
        return $this->hasOne(ConsultationSession::class, 'appointment_id', 'appointment_id');
    }

    /** The prescription issued for this appointment, if the doctor has written one yet. */
    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class, 'appointment_id', 'appointment_id');
    }

    /** The consultation fee payment made when this appointment was booked. */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'appointment_id', 'appointment_id');
    }

    /** The vital signs the doctor recorded at this visit, if any. */
    public function vital(): HasOne
    {
        return $this->hasOne(Vital::class, 'appointment_id', 'appointment_id');
    }

    // The raw `status` column value ("completed") doesn't match the wording
    // shown to users elsewhere (the doctor clicks a "Mark Visited" button) —
    // these two helpers keep the patient's and doctor's appointment lists
    // showing the same word for the same thing, in one place instead of two.
    // The actual wording lives in lang/en|bn/statuses.php so it switches
    // with the site's language instead of being stuck in English.
    private const STATUS_BADGE_CLASSES = [
        'booked' => 'badge-warning',
        'confirmed' => 'badge-success',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger',
        'no_show' => 'badge-danger',
    ];

    public function statusLabel(): string
    {
        // __() just echoes the key back unchanged if there's no matching
        // translation entry — that's the signal used here to fall back to
        // a plain ucfirst() for any status that isn't in statuses.php yet.
        $key = 'statuses.appointment.' . $this->status;
        return __($key) !== $key ? __($key) : ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge';
    }

    /**
     * "10:00 AM - 12:00 PM" if we still know the visiting window's end time
     * (via the template relation — make sure to eager-load it), otherwise
     * just "10:00 AM" as a fallback. There's no personal appointment time
     * to show — appointment_time is only ever the window's start.
     */
    public function timeRangeLabel(): string
    {
        $start = \Illuminate\Support\Carbon::parse($this->appointment_time)->format('g:i A');

        if ($this->template) {
            $end = \Illuminate\Support\Carbon::parse($this->template->end_time)->format('g:i A');
            return "{$start} - {$end}";
        }

        return $start;
    }

    /**
     * Is this appointment's visiting window happening right now? Gates the
     * "Join Video Call" button — before the window starts, or after it
     * ends, there's no live session worth joining, so the button stays
     * disabled instead of dropping someone into an empty room too early
     * or a stale one too late. Make sure `template` is eager-loaded.
     */
    public function isJoinableNow(): bool
    {
        if (!in_array($this->status, ['booked', 'confirmed'], true)) {
            return false;
        }

        if (!$this->template) {
            return false;
        }

        $windowStart = \Illuminate\Support\Carbon::parse($this->appointment_date->toDateString() . ' ' . $this->template->start_time);
        $windowEnd = \Illuminate\Support\Carbon::parse($this->appointment_date->toDateString() . ' ' . $this->template->end_time);

        return now()->between($windowStart, $windowEnd);
    }
}
