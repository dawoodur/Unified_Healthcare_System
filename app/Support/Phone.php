<?php

namespace App\Support;

/**
 * A small helper for dealing with Bangladeshi mobile numbers, since users
 * might type them in several different formats: "01712345678",
 * "+8801712345678", "8801712345678" — we want to accept all of those but
 * always store the SAME format in the database, otherwise two accounts
 * could accidentally register with what's really the same number written
 * two different ways.
 */
class Phone
{
    // A regex (pattern) used by the validation rules in RegistrationController:
    //   ^                start of the string
    //   (?:\+?880)?      optionally, a "+" then "880" (Bangladesh's country code)
    //   01[3-9]          "01" followed by a digit 3-9 (how BD mobile numbers start)
    //   \d{8}            8 more digits
    //   $                end of the string
    // 'regex:' . Phone::BD_MOBILE_REGEX gets used directly inside a Laravel
    // validation rule array, e.g. ['regex:' . Phone::BD_MOBILE_REGEX].
    public const BD_MOBILE_REGEX = '/^(?:\+?880)?01[3-9]\d{8}$/';

    /**
     * Turns any accepted format into one consistent shape: always
     * "01XXXXXXXXX" (11 digits, starting with 0), regardless of how the
     * user typed it in. Examples:
     *   "+8801712345678" -> "01712345678"
     *   "8801712345678"  -> "01712345678"
     *   "01712345678"    -> "01712345678" (already correct, unchanged)
     */
    public static function normalize(string $mobile): string
    {
        // Strip out anything that isn't a digit (spaces, dashes, the "+").
        $digits = preg_replace('/\D/', '', $mobile);

        // If it starts with the country code "880", drop those 3 digits —
        // we'll add the local "0" prefix back on the next line instead.
        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 3);
        }

        // ltrim(..., '0') removes any leading zeros first (so we don't
        // accidentally end up with "001712345678"), then we add exactly
        // one "0" back on the front.
        return '0' . ltrim($digits, '0');
    }
}
