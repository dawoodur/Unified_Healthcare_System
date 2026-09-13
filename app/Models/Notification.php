<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one in-app alert for one account — a new inbox message, an
 * operation request update, expired medicine being removed from stock,
 * etc. `type` is a short machine-readable tag (e.g.
 * 'medicine_expired', 'operation_offered') for anything that wants to
 * branch on it later; `message` is the actual human-readable text shown
 * in the notification list. See NotificationService::notify() for the
 * one place these ever get created.
 */
class Notification extends Model
{
    public $timestamps = false; // this table only tracks created_at, not updated_at
    protected $primaryKey = 'notification_id';

    protected $fillable = ['account_id', 'type', 'message', 'reference_id', 'is_read'];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }
}
