<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * What a generic medicine is for, shared by every brand of it — see the
 * migration for what `source` means (curated / class_inferred / unknown).
 */
class MedicineGenericInfo extends Model
{
    protected $table = 'medicine_generic_info';
    protected $primaryKey = 'generic_info_id';

    protected $fillable = [
        'generic_name', 'uses_en', 'uses_bn', 'cautions_en', 'cautions_bn',
        'is_prescription_only', 'source', 'drug_class', 'needs_review',
    ];

    protected $casts = [
        'cautions_en' => 'array',
        'cautions_bn' => 'array',
        'is_prescription_only' => 'boolean',
        'needs_review' => 'boolean',
    ];

    /** Has enough to actually answer a patient's "what is this for?" question. */
    public function isAnswerable(): bool
    {
        return $this->source !== 'unknown' && $this->uses_en !== null;
    }
}
