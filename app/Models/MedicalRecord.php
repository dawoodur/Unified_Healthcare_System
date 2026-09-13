<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one item in a patient's medical history — a prescription
 * (auto-added the moment a doctor issues one, see PrescriptionController),
 * a lab result, a diagnosis note, or a document the patient uploaded
 * themselves. `reference_id` optionally points at another table's row
 * depending on `record_type` (e.g. a `prescriptions.prescription_id`
 * when record_type is 'prescription') — it has no FK constraint because
 * which table it points at varies, so the database can't enforce it; the
 * app is careful to only ever set it consistently with record_type.
 */
class MedicalRecord extends Model
{
    public $timestamps = false; // this table only has created_at, not updated_at
    protected $table = 'medical_records';
    protected $primaryKey = 'record_id';

    protected $fillable = ['patient_id', 'record_type', 'reference_id', 'file_path', 'description', 'created_by_account_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    /** Whoever's account created this row — a patient uploading their own document, or a doctor issuing a prescription. */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'created_by_account_id', 'account_id');
    }

    // Wording lives in lang/en|bn/statuses.php so it switches with locale.
    public function typeLabel(): string
    {
        $key = 'statuses.medical_record_type.' . $this->record_type;
        return __($key) !== $key ? __($key) : ucfirst(str_replace('_', ' ', $this->record_type));
    }
}
