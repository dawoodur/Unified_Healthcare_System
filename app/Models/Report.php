<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one bug/system report submitted by any logged-in user (any of
 * the 5 non-admin roles). Admin reviews these and can leave a response.
 */
class Report extends Model
{
    public $timestamps = false; // this table tracks its own created_at/resolved_at instead

    protected $primaryKey = 'report_id';

    protected $fillable = [
        'reporter_account_id', 'subject', 'description', 'status', 'admin_response', 'resolved_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'reporter_account_id', 'account_id');
    }
}
