<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one online appointment's video-call "room." Created the first
 * time either the patient or doctor opens the consultation page for that
 * appointment (see ConsultationController::show()). The actual audio/video
 * is peer-to-peer between the two browsers (WebRTC) — this row and
 * WebrtcSignal just track the room's state and relay the handshake
 * messages needed to connect them, since plain PHP/Apache has no
 * persistent websocket server to do that "properly."
 */
class ConsultationSession extends Model
{
    public $timestamps = false;
    protected $table = 'consultation_sessions';
    protected $primaryKey = 'session_id';

    protected $fillable = ['appointment_id', 'started_at', 'ended_at', 'status', 'summary'];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }

    /** The offer/answer/ICE-candidate handshake messages exchanged in this room. */
    public function signals(): HasMany
    {
        return $this->hasMany(WebrtcSignal::class, 'session_id', 'session_id');
    }

    /** The text chat exchanged in this room, oldest first if you order by message_id. */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'session_id', 'session_id');
    }
}
