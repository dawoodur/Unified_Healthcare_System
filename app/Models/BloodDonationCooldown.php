<?php

namespace App\Models;

/**
 * How long a donor waits between donations, in one place so the model, the
 * service, the patient countdown and the hospital's donor email can never
 * disagree about it.
 */
class BloodDonationCooldown
{
    public const DAYS = 3;
}
