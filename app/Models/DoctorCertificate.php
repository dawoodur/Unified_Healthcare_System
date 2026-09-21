<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one certificate file a doctor uploaded, for admin to review.
 * A doctor can have SEVERAL of these over time (e.g. if a first one gets
 * rejected and they upload a replacement) — that's why this is its own
 * table/model instead of just a single file_path column on Doctor.
 */
class DoctorCertificate extends Model
{
    public $timestamps = false; // this table tracks its own uploaded_at/reviewed_at instead
    protected $primaryKey = 'certificate_id';

    protected $fillable = ['doctor_id', 'file_path', 'uploaded_at', 'verification_status', 'reviewed_by_admin_id', 'reviewed_at'];

    // Turn these two columns into real date objects instead of plain strings.
    protected $casts = [
        'uploaded_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /** $certificate->doctor gives back which Doctor uploaded this file. */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }
}
