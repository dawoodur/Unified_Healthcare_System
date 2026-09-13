<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one 6-digit code that was ever generated, for any purpose
 * (registration verification, login 2FA, and later: doctor record-access
 * approval, delivery confirmation). All the logic that creates, emails, and
 * checks these lives in app/Services/OtpService.php — this file is just the
 * "one row of this table" shape.
 */
class OtpVerification extends Model
{
    public $timestamps = false;

    // Eloquent guesses a model's table name from its class name (OtpVerification
    // -> "otp_verifications" would actually be guessed correctly here automatically,
    // but we spell it out explicitly so it's obvious at a glance which table this is).
    protected $table = 'otp_verifications';
    protected $primaryKey = 'otp_id';

    protected $fillable = ['account_id', 'purpose', 'reference_id', 'otp_code', 'expires_at', 'is_used', 'attempts'];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /** $otp->account gives back which Account this code was sent to. */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }
}
