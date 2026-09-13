<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one message inside one inbox conversation. See
 * Conversation.php for how conversations are found/created, and
 * InboxController for the polling-based send/receive flow (same shape as
 * the consultation-room chat, just not tied to an appointment).
 */
class InboxMessage extends Model
{
    public $timestamps = false; // this table tracks its own sent_at instead
    protected $primaryKey = 'message_id';

    protected $fillable = ['conversation_id', 'sender_account_id', 'message_text', 'is_read'];

    protected $casts = [
        'sent_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sender_account_id', 'account_id');
    }
}
