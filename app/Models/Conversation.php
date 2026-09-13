<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one inbox conversation between two accounts, any two roles
 * that are allowed to message each other (see
 * InboxController::ALLOWED_CHAT_ROLES). Always stored with the smaller
 * account_id as participant_low_id and the larger as participant_high_id
 * — that's what the unique index is built on, so the same two people can
 * never end up with two separate conversation rows no matter which of
 * them starts it.
 */
class Conversation extends Model
{
    public $timestamps = false; // this table tracks its own created_at/last_message_at instead
    protected $primaryKey = 'conversation_id';

    protected $fillable = ['participant_low_id', 'participant_high_id', 'last_message_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function participantLow(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'participant_low_id', 'account_id');
    }

    public function participantHigh(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'participant_high_id', 'account_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InboxMessage::class, 'conversation_id', 'conversation_id');
    }

    /** Whichever of the two participants ISN'T the given account — for showing "who am I talking to". */
    public function otherParticipant(int $accountId): Account
    {
        return $this->participant_low_id === $accountId ? $this->participantHigh : $this->participantLow;
    }

    /**
     * Finds the existing conversation between these two accounts, or
     * starts a new one — the one and only place a conversations row gets
     * created, so the low/high ordering rule can never be broken.
     */
    public static function between(int $accountIdA, int $accountIdB): self
    {
        return self::firstOrCreate([
            'participant_low_id' => min($accountIdA, $accountIdB),
            'participant_high_id' => max($accountIdA, $accountIdB),
        ]);
    }
}
