<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one keyword that hints at one disease. The table's real
 * primary key is the (disease_id, keyword) pair, not a single ID column —
 * same shape as FaqKeyword, for the same reason (only ever bulk-queried).
 */
class DiseaseKeyword extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $table = 'disease_keywords';
    protected $primaryKey = 'disease_id';

    protected $fillable = ['disease_id', 'keyword'];

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class, 'disease_id', 'disease_id');
    }
}
