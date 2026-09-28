<?php

namespace App\Services;

use App\Mail\EmergencyBloodRequestMail;
use App\Models\BloodDonation;
use App\Models\BloodDonationCooldown;
use App\Models\BloodRequest;
use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Blood donation: a patient logs a donation, the hospital confirms it, and
 * confirmation is what starts the clock and pays the reward points.
 *
 * Everything hangs off confirmed_at rather than the date the patient typed,
 * so the countdown a patient sees, the eligibility check, and the hospital's
 * emergency donor email all agree — and a donation nobody confirms neither
 * earns points nor blocks the donor.
 */
class BloodDonationService
{
    public function __construct(private RewardPointService $rewards, private NotificationService $notifications)
    {
    }

    /** The patient's most recent CONFIRMED donation — what the cooldown is measured from. */
    public function latestConfirmed(Patient $patient): ?BloodDonation
    {
        return $patient->bloodDonations()
            ->where('status', 'confirmed')
            ->orderByDesc('confirmed_at')
            ->first();
    }

    /** A donation already logged and still waiting on the hospital blocks logging another. */
    public function pendingDonation(Patient $patient): ?BloodDonation
    {
        return $patient->bloodDonations()->where('status', 'pending')->orderByDesc('donation_id')->first();
    }

    /** Is this patient allowed to log a donation RIGHT NOW? */
    public function isEligible(Patient $patient): bool
    {
        $nextEligible = $this->nextEligibleDate($patient);

        return $nextEligible === null || $nextEligible->isPast();
    }

    /** The exact moment the countdown ends — null when they can donate already. */
    public function nextEligibleDate(Patient $patient): ?Carbon
    {
        $endsAt = $this->latestConfirmed($patient)?->cooldownEndsAt();

        return $endsAt && $endsAt->isFuture() ? $endsAt : null;
    }

    /**
     * Logs a donation as PENDING. It counts for nothing until the hospital
     * confirms it — see confirm(). Re-checks eligibility server-side; the
     * form is hidden when ineligible, but that is never the real enforcement.
     */
    public function recordDonation(Patient $patient, string $donatedAt, ?Hospital $hospital): array
    {
        if ($pending = $this->pendingDonation($patient)) {
            return ['ok' => false, 'message' => 'You already logged a donation on ' . $pending->donated_at->format('M j, Y') . ' that is waiting for the hospital to confirm.'];
        }

        if (!$this->isEligible($patient)) {
            return ['ok' => false, 'message' => 'You can donate again on ' . $this->nextEligibleDate($patient)->format('M j, Y g:i A') . '.'];
        }

        if (Carbon::parse($donatedAt)->isFuture()) {
            return ['ok' => false, 'message' => "The donation date can't be in the future."];
        }

        BloodDonation::create([
            'patient_id' => $patient->patient_id,
            'hospital_id' => $hospital?->hospital_id,
            'donated_at' => $donatedAt,
            'status' => 'pending',
        ]);

        return ['ok' => true, 'message' => 'Logged — waiting for ' . ($hospital?->hospital_name ?? 'the hospital') . ' to confirm it. Your reward points and countdown start once they do.'];
    }

    /**
     * Hospital confirms the donation really happened: the countdown starts
     * now, and the donor is credited their reward points (award() is keyed on
     * the donation id, so confirming twice cannot pay twice).
     */
    public function confirm(BloodDonation $donation, Hospital $hospital): array
    {
        if ($donation->hospital_id !== $hospital->hospital_id) {
            return ['ok' => false, 'message' => 'That donation was logged for a different hospital.'];
        }

        if (!$donation->isPending()) {
            return ['ok' => false, 'message' => 'That donation has already been reviewed.'];
        }

        $donation->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        $patient = $donation->patient;
        // Kept in step so the donor email filter and anything else reading
        // the cached column agree with the confirmed history.
        $patient->update(['last_donated_at' => $donation->donated_at]);

        $this->rewards->award($patient, 'blood_donation', $donation->donation_id);

        return [
            'ok' => true,
            'points' => $this->rewards->pointsFor('blood_donation'),
            'message' => 'Confirmed — ' . $patient->full_name . ' earned ' . $this->rewards->pointsFor('blood_donation') . ' reward points.',
        ];
    }

    /** Hospital says it did not happen: no points, no countdown, donor stays eligible. */
    public function reject(BloodDonation $donation, Hospital $hospital, ?string $reason = null): array
    {
        if ($donation->hospital_id !== $hospital->hospital_id) {
            return ['ok' => false, 'message' => 'That donation was logged for a different hospital.'];
        }

        if (!$donation->isPending()) {
            return ['ok' => false, 'message' => 'That donation has already been reviewed.'];
        }

        $donation->update(['status' => 'rejected', 'reject_reason' => $reason]);

        return ['ok' => true, 'message' => 'Marked as not confirmed — the donor can log a donation again.'];
    }

    /**
     * Every patient who (a) has this exact blood group, (b) is not inside the
     * cooldown from a confirmed donation, and (c) has a real, active account
     * to actually receive the email.
     */
    public function eligibleDonors(string $bloodGroup): Collection
    {
        return Patient::where('blood_group', $bloodGroup)
            ->whereDoesntHave('bloodDonations', fn ($q) => $q
                ->where('status', 'confirmed')
                ->where('confirmed_at', '>', now()->subDays(BloodDonationCooldown::DAYS)))
            ->whereHas('account', fn ($q) => $q->where('is_active', true)->where('is_verified', true))
            ->with('account')
            ->get();
    }

    /**
     * Hospital sends an emergency call for one blood type. Emails every
     * currently-eligible donor of that type individually — if one send
     * fails (bad SMTP, etc.) it's logged and skipped rather than aborting
     * the whole batch, same resilience idea as OtpService::issue() — and
     * also gives each of them an in-app notification (NotificationService),
     * since an email alone is easy to miss for something this time-
     * sensitive. The notification always fires even if that donor's email
     * happened to fail, so a bad email address never means a donor hears
     * nothing at all.
     */
    public function sendEmergencyRequest(Hospital $hospital, string $bloodGroup, ?string $message): array
    {
        $donors = $this->eligibleDonors($bloodGroup);

        $bloodRequest = BloodRequest::create([
            'hospital_id' => $hospital->hospital_id,
            'blood_group' => $bloodGroup,
            'message' => $message,
            'recipient_count' => $donors->count(),
        ]);

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

            $this->notifications->notify(
                $patient->account,
                'blood_request',
                "Urgent: {$hospital->hospital_name} needs {$bloodGroup} blood donors" . ($message ? " — {$message}" : '.'),
                $bloodRequest->blood_request_id
            );
        }

        return ['ok' => true, 'sent' => $sent, 'total' => $donors->count(), 'bloodRequest' => $bloodRequest];
    }
}
