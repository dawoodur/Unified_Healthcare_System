<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Medicine information fetched from a public drug API, waiting for an admin
 * to approve, edit or reject it. Patients never see a draft — only
 * medicine_generic_info reaches the health chat.
 */
class MedicineInfoDraft extends Model
{
    protected $table = 'medicine_info_drafts';
    protected $primaryKey = 'draft_id';

    protected $fillable = [
        'generic_name', 'source', 'source_ref', 'queried_as', 'uses_en', 'cautions_en',
        'suggested_prescription_only', 'status', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'cautions_en' => 'array',
        'suggested_prescription_only' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'reviewed_by', 'account_id');
    }

    /** The public label page this text came from, so a reviewer can check the source. */
    public function sourceUrl(): ?string
    {
        return $this->source === 'openfda' && $this->source_ref
            ? 'https://dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=' . $this->source_ref
            : null;
    }
}
