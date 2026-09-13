<?php

namespace App\Services;

use App\Models\Prescription;

/**
 * Builds a short wrap-up summary for an online consultation once its
 * prescription is issued (see PrescriptionController::store()). This is
 * template-based, not real language-model summarization — the same
 * "rule-based, no external API" spirit as SymptomCheckerController and
 * FaqController — built entirely from data the server actually has: the
 * in-room chat log, the diagnosis notes, and what was just prescribed.
 * There's no transcript of the actual spoken conversation, since
 * WebRTC audio/video is peer-to-peer and never touches this server.
 */
class ConsultationSummaryService
{
    /** Builds the summary text for one just-issued prescription's consultation, or null if there's no consultation session to summarize. */
    public function generate(Prescription $prescription): ?string
    {
        $appointment = $prescription->appointment;
        $session = $appointment->consultationSession;

        if (!$session) {
            return null;
        }

        $lines = [];

        $lines[] = 'Online consultation on ' . $appointment->appointment_date->format('M j, Y')
            . ' between Dr. ' . $prescription->doctor->full_name . ' and ' . $prescription->patient->full_name . '.';

        if ($session->started_at) {
            $duration = $session->started_at->diffForHumans(now(), true);
            $lines[] = "Call duration: about {$duration}.";
        }

        $messageCount = $session->messages()->count();
        $lines[] = $messageCount > 0
            ? "{$messageCount} chat message(s) exchanged during the call."
            : 'No chat messages were exchanged during the call.';

        if ($prescription->diagnosis_notes) {
            $lines[] = 'Diagnosis notes: ' . $prescription->diagnosis_notes;
        }

        $medicineNames = $prescription->items->map(fn ($item) => $item->medicine->generic_name ?? null)->filter()->values();
        if ($medicineNames->isNotEmpty()) {
            $lines[] = 'Medicines prescribed: ' . $medicineNames->implode(', ') . '.';
        }

        $testNames = $prescription->facilityItems->map(fn ($item) => $item->facilityType->name ?? null)->filter()->values();
        if ($testNames->isNotEmpty()) {
            $lines[] = 'Tests/operations recommended: ' . $testNames->implode(', ') . '.';
        }

        return implode(' ', $lines);
    }
}
