<?php

namespace App\Services;

use App\Mail\EmergencyBloodRequestMail;
use App\Models\BloodDonation;
use App\Models\BloodRequest;
use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Blood donation eligibility (every 3 months) and the hospital-side
 * emergency call for a specific blood type — both live here because
 * "who's eligible to donate right now" is the one piece of logic both
 * sides actually care about (a patient checking their own status, and a
 * hospital's emergency email needing to filter exactly the same way).
 */
class BloodDonationService
{
    private const COOLDOWN_MONTHS = 3;

    /** Is this patient allowed to log a donation RIGHT NOW? */
    public function isEligible(Patient $patient): bool
    {
        if (!$patient->last_donated_at) {
            return true; // never donated before — always eligible
        }

        return $patient->last_donated_at->lte(now()->subMonths(self::COOLDOWN_MONTHS));
    }

    /** When this patient next becomes eligible — null if they're eligible already (or have never donated). */
    public function nextEligibleDate(Patient $patient): ?Carbon
    {
        if ($this->isEligible($patient)) {
            return null;
        }

        return $patient->last_donated_at->copy()->addMonths(self::COOLDOWN_MONTHS);
    }

    /**
     * Logs a donation — re-checks eligibility server-side (the "Log a
     * donation" form is hidden/disabled in the UI when ineligible, but
     * that's never trusted as the real enforcement). Takes the MAX of
     * every donation on file (not just blindly the new date) as the
     * refreshed last_donated_at, in case a patient ever logs one out of
     * chronological order.
     */
    public function recordDonation(Patient $patient, string $donatedAt, ?Hospital $hospital): array
    {
        if (!$this->isEligible($patient)) {
            return ['ok' => false, 'message' => 'You can donate again on ' . $this->nextEligibleDate($patient)->format('M j, Y') . '.'];
        }

        if (Carbon::parse($donatedAt)->isFuture()) {
            return ['ok' => false, 'message' => 'The donation date can\'t be in the future.'];
        }

        BloodDonation::create([
            'patient_id' => $patient->patient_id,
            'hospital_id' => $hospital?->hospital_id,
            'donated_at' => $donatedAt,
        ]);

        $latest = $patient->bloodDonations()->max('donated_at');
        $patient->update(['last_donated_at' => $latest]);

        return ['ok' => true, 'message' => 'Thank you for donating! Logged for ' . Carbon::parse($donatedAt)->format('M j, Y') . '.'];
    }

    /**
     * Every patient who (a) has this exact blood group, (b) is eligible to
     * donate right now (see isEligible()), and (c) has a real, active
     * account to actually receive the email.
     */
    public function eligibleDonors(string $bloodGroup): Collection
    {
        return Patient::where('blood_group', $bloodGroup)
            ->where(function ($q) {
                $q->whereNull('last_donated_at')
                    ->orWhere('last_donated_at', '<=', now()->subMonths(self::COOLDOWN_MONTHS)->toDateString());
            })
            ->whereHas('account', fn ($q) => $q->where('is_active', true)->where('is_verified', true))
            ->with('account')
            ->get();
    }

    /**
     * Hospital sends an emergency call for one blood type. Emails every
     * currently-eligible donor of that type individually — if one send
     * fails (bad SMTP, etc.) it's logged and skipped rather than aborting
     * the whole batch, same resilience idea as OtpService::issue().
     */
    public function sendEmergencyRequest(Hospital $hospital, string $bloodGroup, ?string $message): array
    {
        $donors = $this->eligibleDonors($bloodGroup);

        $sent = 0;
        foreach ($donors as $patient) {
            try {
                Mail::to($patient->account->email)->send(new EmergencyBloodRequestMail(
                    $patient->full_name,
                    $hospital->hospital_name,
                    $bloodGroup,
                    $message,
                ));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $bloodRequest = BloodRequest::create([
            'hospital_id' => $hospital->hospital_id,
            'blood_group' => $bloodGroup,
            'message' => $message,
            'recipient_count' => $sent,
        ]);

        return ['ok' => true, 'sent' => $sent, 'total' => $donors->count(), 'bloodRequest' => $bloodRequest];
    }
}
