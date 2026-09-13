<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one text message sent during a consultation's video-call
 * "room" (see ConsultationSession.php) — the patient and doctor's browsers
 * poll for new ones the same way they poll for WebRTC signals, since
 * there's no persistent websocket server to push these instantly.
 */
class ChatMessage extends Model
{
    public $timestamps = false; // this table uses sent_at, not created_at/updated_at
    protected $table = 'chat_messages';
    protected $primaryKey = 'message_id';

    protected $fillable = ['session_id', 'sender_account_id', 'message_text', 'is_read'];

    protected $casts = [
        'sent_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConsultationSession::class, 'session_id', 'session_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sender_account_id', 'account_id');
    }
}
