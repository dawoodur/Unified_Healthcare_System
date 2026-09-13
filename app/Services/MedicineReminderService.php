<?php

namespace App\Services;

use App\Models\MedicineReminderTime;
use App\Models\PrescriptionItem;

/**
 * Auto-schedules "take your medicine" reminder times from a prescription
 * item's free-text frequency (e.g. "Twice daily", "Every 6 hours") the
 * moment a doctor issues the prescription — a patient doesn't have to
 * remember to go set these up themselves; see
 * PrescriptionController::store() for where this gets called. They can
 * still add/remove times afterward from My Prescriptions (see
 * MedicineReminderController) — this only picks sensible starting
 * defaults, it doesn't lock anything in.
 *
 * Rule-based text matching, same "no external API" spirit as
 * SymptomCheckerController/FaqController — not a real NLP parser, just
 * keyword/pattern matching against the handful of phrasings doctors
 * actually use in this app (see the frequency examples in
 * doctor/partials/prescription-form.blade.php).
 */
class MedicineReminderService
{
    // One clock-time table per daily dose count — after breakfast/lunch/
    // dinner-ish spacing, not evenly spaced by the clock, since that's
    // when a patient realistically already has a routine to hang a
    // reminder on.
    private const TIME_TABLES = [
        1 => ['09:00'],
        2 => ['09:00', '21:00'],
        3 => ['08:00', '14:00', '21:00'],
        4 => ['08:00', '13:00', '18:00', '22:00'],
    ];

    /**
     * Creates default reminder times for one prescription item based on
     * its frequency text. Does nothing if frequency is blank, or if this
     * item already has reminder times (never overwrites a patient's own
     * edits — this only ever fires once, right after the item is created).
     * Returns how many reminder times were created.
     */
    public function scheduleDefaults(PrescriptionItem $item): int
    {
        if (empty($item->frequency)) {
            return 0;
        }

        if ($item->reminderTimes()->exists()) {
            return 0;
        }

        $times = self::TIME_TABLES[$this->dosesPerDay($item->frequency)];

        foreach ($times as $time) {
            MedicineReminderTime::create([
                'prescription_item_id' => $item->prescription_item_id,
                'reminder_time' => $time,
            ]);
        }

        return count($times);
    }

    /** How many times a day this frequency text implies — always 1-4, defaulting to 1 when it can't tell. */
    private function dosesPerDay(string $frequency): int
    {
        $text = mb_strtolower($frequency);

        // "Every 6 hours" / "every 8 hour(s)" -> roughly how many doses
        // fit in a day, capped at 4 so this never schedules something
        // unreasonable like hourly reminders.
        if (preg_match('/every\s+(\d+)\s*hour/', $text, $m)) {
            $hours = max(1, (int) $m[1]);
            return max(1, min(4, (int) round(24 / $hours)));
        }

        if (str_contains($text, 'four times') || str_contains($text, '4 times')) {
            return 4;
        }

        if (str_contains($text, 'three times') || str_contains($text, '3 times') || str_contains($text, 'thrice')) {
            return 3;
        }

        if (str_contains($text, 'twice') || str_contains($text, 'two times') || str_contains($text, '2 times')) {
            return 2;
        }

        // "Once daily", "once a day", "1 time", or anything else that
        // mentions a frequency at all but doesn't match a specific count
        // above — one reminder is always a safe, non-annoying default.
        return 1;
    }
}
