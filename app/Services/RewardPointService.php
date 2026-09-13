<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Patient;
use App\Models\RewardPointLedger;

/**
 * Reward points: a patient earns points for rating a provider, and can
 * spend them for a discount on a medicine order. The three numbers that
 * define the whole system live in app_settings (see DatabaseSeeder.php),
 * not hardcoded here, so they could be tuned later without touching code:
 *   POINTS_PER_REVIEW               - points earned per NEW rating (editing an existing one doesn't re-earn)
 *   POINTS_PER_PERCENT_DISCOUNT     - how many points buy 1% off (10 -> 10pts=1%, 100pts=10%)
 *   MAX_REDEEMABLE_POINTS_PER_ORDER - hard cap on points usable in a single order
 */
class RewardPointService
{
    /**
     * Credits points to a patient's balance and logs why. A source with a
     * concrete source_id can only earn once, so retries cannot duplicate
     * points for the same review, delivered medicine order, or lab test.
     */
    public function award(Patient $patient, string $sourceType, ?int $sourceId = null): void
    {
        if ($sourceId !== null && RewardPointLedger::where('patient_id', $patient->patient_id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists()) {
            return;
        }

        $points = $this->pointsFor($sourceType);
        if ($points <= 0) {
            return;
        }

        RewardPointLedger::create([
            'patient_id' => $patient->patient_id,
            'points' => $points,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);

        $patient->increment('reward_points_balance', $points);
    }

    /** Points earned for each supported reward source. */
    public function pointsFor(string $sourceType): int
    {
        $reviewPoints = max(0, (int) AppSetting::get('POINTS_PER_REVIEW', '1'));

        $setting = match ($sourceType) {
            'review' => 'POINTS_PER_REVIEW',
            'medicine_purchase' => 'POINTS_PER_MEDICINE_PURCHASE',
            'lab_test_purchase' => 'POINTS_PER_LAB_TEST_PURCHASE',
            default => null,
        };

        if ($setting === null) {
            return 0;
        }

        // Existing installations already have POINTS_PER_REVIEW. The two
        // purchase settings are optional and intentionally fall back to the
        // same one-point policy until an admin chooses different values.
        return max(0, (int) AppSetting::get($setting, (string) $reviewPoints));
    }

    /**
     * The most points this patient could redeem right now, rounded DOWN to
     * a whole multiple of POINTS_PER_PERCENT_DISCOUNT — every choice
     * offered to a patient should always land on a clean whole-percent
     * discount, never something like "1.5% off."
     */
    public function maxRedeemable(Patient $patient): int
    {
        $step = max(1, (int) AppSetting::get('POINTS_PER_PERCENT_DISCOUNT', '10'));
        $cap = (int) AppSetting::get('MAX_REDEEMABLE_POINTS_PER_ORDER', '100');

        $usable = min($patient->reward_points_balance, $cap);

        return intdiv($usable, $step) * $step;
    }

    /** Every valid "spend this many points" choice for this patient, smallest to largest — e.g. [0, 10, 20, ..., 100]. */
    public function redemptionOptions(Patient $patient): array
    {
        $step = max(1, (int) AppSetting::get('POINTS_PER_PERCENT_DISCOUNT', '10'));
        $max = $this->maxRedeemable($patient);

        $options = [];
        for ($points = 0; $points <= $max; $points += $step) {
            $options[] = $points;
        }

        return $options;
    }

    /** What percent discount a given number of points is worth. */
    public function percentFor(int $points): float
    {
        $step = (int) AppSetting::get('POINTS_PER_PERCENT_DISCOUNT', '10');

        return $step > 0 ? $points / $step : 0.0;
    }

    /**
     * Pure calculation, no database writes — safe to call before an order
     * even exists yet (e.g. to show a live preview on the checkout page).
     * Re-clamps $requestedPoints to what's ACTUALLY redeemable right now —
     * never trusts a number a form happened to submit. Returns
     * ['points' => int, 'discount' => float].
     */
    public function previewRedemption(Patient $patient, int $requestedPoints, float $subtotal): array
    {
        $safePoints = max(0, min($requestedPoints, $this->maxRedeemable($patient)));
        $discount = $safePoints > 0 ? round($subtotal * $this->percentFor($safePoints) / 100, 2) : 0.0;

        return ['points' => $safePoints, 'discount' => $discount];
    }

    /**
     * Actually spends the points: logs a negative ledger row and decrements
     * the cached balance. Call this AFTER the order row already exists
     * (so $orderId is real), inside the SAME database transaction that
     * created it — see MedicineOrderService::placeOrder().
     */
    public function commitRedemption(Patient $patient, int $points, int $orderId): void
    {
        if ($points <= 0) {
            return;
        }

        RewardPointLedger::create([
            'patient_id' => $patient->patient_id,
            'points' => -$points,
            'source_type' => 'redemption',
            'source_id' => $orderId,
        ]);

        $patient->decrement('reward_points_balance', $points);
    }
}
