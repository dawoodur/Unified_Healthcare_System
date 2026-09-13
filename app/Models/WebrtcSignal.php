<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one message in the WebRTC "handshake" two browsers need to
 * connect a peer-to-peer video call (an SDP offer, an SDP answer, or one
 * ICE candidate — see resources/views/consultation/room.blade.php for what
 * actually generates these). Both browsers poll for new rows addressed to
 * them (see ConsultationController::pollSignals()) since there's no
 * websocket server to push these instantly.
 */
class WebrtcSignal extends Model
{
    public $timestamps = false;
    protected $table = 'webrtc_signals';
    protected $primaryKey = 'signal_id';

    protected $fillable = ['session_id', 'sender_account_id', 'signal_type', 'payload'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConsultationSession::class, 'session_id', 'session_id');
    }
}
