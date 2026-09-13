<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\Account;
use App\Models\AppSetting;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Mail;

/**
 * Every piece of OTP (one-time-passcode) logic in the whole app lives in
 * this one class: generating a code, emailing it, and checking a submitted
 * code. It's used identically by registration verification, login 2FA, and
 * (in later phases) doctor record-access approval and delivery confirmation
 * — so this file only needed to be written once instead of four times.
 *
 * A "Service" class in Laravel isn't a special built-in concept — it's just
 * a convention: a plain PHP class for logic that doesn't cleanly belong to
 * one Model or one Controller. Nothing magic about the word "Service" itself.
 */
class OtpService
{
    // A fixed lookup table: purpose code -> the human-readable text used as
    // the email's subject line and heading.
    public const PURPOSE_LABELS = [
        'registration_verify' => 'Verify your registration',
        'login' => 'Your login verification code',
        'record_access' => 'Approve medical record access',
        'delivery_confirmation' => 'Confirm your delivery',
        'password_reset' => 'Reset your password',
    ];

    /**
     * Works out what name to greet someone by in an email, since "name"
     * lives in a different column depending on the role (a patient has
     * full_name, a hospital has hospital_name, a pharmacy has
     * pharmacy_name). Falls back to their email if nothing else fits.
     */
    public function accountDisplayName(Account $account): string
    {
        $profile = $account->profile(); // the role-specific row — see Account::profile()

        if ($profile === null) {
            return $account->email;
        }

        // ?-> (nullsafe) means "get this property, but if the object is
        // null, just return null instead of crashing." Not every profile
        // type has every one of these three columns, so we try each in
        // turn and use whichever one actually exists and isn't empty.
        return $profile->full_name
            ?? $profile->hospital_name
            ?? $profile->pharmacy_name
            ?? $account->email;
    }

    /**
     * Creates a fresh OTP for the given account+purpose, invalidating any previous
     * unused one, and emails it. Returns ['otp' => OtpVerification, 'sent' => bool].
     */
    public function issue(Account $account, string $purpose, ?int $referenceId = null): array
    {
        // If this account already had an un-used code for the same purpose
        // (e.g. they clicked "resend"), mark it used so it can never be
        // entered later — only the newest code should ever work.
        OtpVerification::where('account_id', $account->account_id)
            ->where('purpose', $purpose)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        $expiryMinutes = (int) AppSetting::get('OTP_EXPIRY_MIN', '10');

        $otp = OtpVerification::create([
            'account_id' => $account->account_id,
            'purpose' => $purpose,
            'reference_id' => $referenceId,
            // random_int(0, 999999) picks a random number 0-999999, then
            // str_pad(..., 6, '0', STR_PAD_LEFT) pads it to exactly 6 digits
            // with leading zeros (so "42" becomes "000042").
            'otp_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        $sent = true;
        try {
            Mail::to($account->email)->send(new OtpMail(
                $this->accountDisplayName($account),
                self::PURPOSE_LABELS[$purpose] ?? 'Your verification code',
                $otp->otp_code,
                $expiryMinutes,
            ));
        } catch (\Throwable $e) {
            // Sending can fail (e.g. wrong SMTP password) — don't let that
            // crash the whole registration/login. Log the real error for
            // us to diagnose, and tell the caller it didn't send so they
            // can show the user a helpful message instead of a blank error.
            report($e);
            $sent = false;
        }

        return ['otp' => $otp, 'sent' => $sent];
    }

    /** Is enough time passed since the last code that we can send another? */
    public function canResend(Account $account, string $purpose): bool
    {
        $cooldown = (int) AppSetting::get('OTP_RESEND_COOLDOWN_SEC', '60');

        $last = OtpVerification::where('account_id', $account->account_id)
            ->where('purpose', $purpose)
            ->orderByDesc('otp_id') // newest first
            ->first();

        if (!$last) {
            return true; // never sent one before — nothing to wait for
        }

        return $last->created_at->diffInSeconds(now()) >= $cooldown;
    }

    /**
     * Checks a submitted code. Returns ['ok' => bool, 'message' => string, 'reference_id' => int|null].
     * $message is always safe to show directly to the user.
     */
    public function verify(Account $account, string $purpose, string $submittedCode): array
    {
        $maxAttempts = (int) AppSetting::get('OTP_MAX_ATTEMPTS', '5');

        $otp = OtpVerification::where('account_id', $account->account_id)
            ->where('purpose', $purpose)
            ->where('is_used', false)
            ->orderByDesc('otp_id')
            ->first();

        if (!$otp) {
            return ['ok' => false, 'message' => 'No pending verification code. Please request a new one.', 'reference_id' => null];
        }

        if ($otp->attempts >= $maxAttempts) {
            return ['ok' => false, 'message' => 'Too many incorrect attempts. Please request a new code.', 'reference_id' => null];
        }

        if ($otp->expires_at->isPast()) {
            return ['ok' => false, 'message' => 'This code has expired. Please request a new one.', 'reference_id' => null];
        }

        // hash_equals (instead of a plain === comparison) protects against
        // "timing attacks" — a way of guessing a code one character at a
        // time by measuring how many microseconds a comparison takes.
        // Overkill for a 6-digit code with a lockout, but costs nothing to
        // do properly.
        if (!hash_equals($otp->otp_code, $submittedCode)) {
            $otp->increment('attempts'); // += 1 and save, in one step
            return ['ok' => false, 'message' => 'Incorrect code. Please try again.', 'reference_id' => null];
        }

        $otp->update(['is_used' => true]);

        return ['ok' => true, 'message' => 'Verified.', 'reference_id' => $otp->reference_id];
    }
}
