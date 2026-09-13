<?php

namespace App\Http\Controllers;

use App\Models\MedicineReminderTime;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Patient-managed "take your medicine" alarm times for a prescribed item —
 * see SendMedicineRemindersCommand for the scheduled job that actually
 * fires the notification once a reminder_time comes around. Both actions
 * here just redirect back to My Prescriptions, where the reminder times
 * are managed inline per medicine.
 */
class MedicineReminderController extends Controller
{
    /** Patient: adds a daily reminder time for one of their prescribed items (POST /patient/prescriptions/{item}/reminders). */
    public function store(Request $request, PrescriptionItem $item)
    {
        $this->authorizeOwner($item);

        $data = $request->validate([
            'reminder_time' => ['required', 'date_format:H:i'],
        ]);

        if (!$item->isDosingActive()) {
            return back()->withErrors(['reminder_time' => "This medicine's course has already finished — no need for a reminder."]);
        }

        MedicineReminderTime::create([
            'prescription_item_id' => $item->prescription_item_id,
            'reminder_time' => $data['reminder_time'],
        ]);

        return back()->with('success', 'Reminder added.');
    }

    /** Patient: removes a reminder time (POST /patient/reminders/{reminder}/delete). */
    public function destroy(MedicineReminderTime $reminder)
    {
        $this->authorizeOwner($reminder->prescriptionItem);

        $reminder->delete();

        return back()->with('success', 'Reminder removed.');
    }

    private function authorizeOwner(PrescriptionItem $item): void
    {
        $item->load('prescription');
        if ($item->prescription->patient_id !== Auth::user()->patient->patient_id) {
            abort(403);
        }
    }
}
