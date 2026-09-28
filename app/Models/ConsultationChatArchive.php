<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one ended consultation's chat, snapshotted for admin review
 * only — see ConsultationChatService::archiveForAdmin(). Never read by the
 * patient or doctor themselves (the live chat stays ephemeral for them,
 * exactly as before); only Admin > Consultation chat history, and only
 * for 7 days before ConsultationChatArchivePurgeCommand deletes it.
 */
class ConsultationChatArchive extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'archive_id';

    protected $fillable = ['session_id', 'appointment_id', 'patient_account_id', 'doctor_account_id', 'archived_at'];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConsultationSession::class, 'session_id', 'session_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }

    public function patientAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'patient_account_id', 'account_id');
    }

    public function doctorAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'doctor_account_id', 'account_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConsultationChatArchiveMessage::class, 'archive_id', 'archive_id');
    }
}
