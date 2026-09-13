<?php

namespace App\Console\Commands;

use App\Models\MedicineReminderTime;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * The everyday "time to take your medicine" alarm — distinct from
 * SendRefillRemindersCommand, which reminds a patient to REORDER once a
 * course is about to run out. A patient sets one or more daily times per
 * prescribed item (see MedicineReminderController); this checks every
 * minute for reminder_time matching right now and notifies in-app.
 *
 * last_sent_at guards against firing twice for the same time-of-day: it's
 * only reset implicitly by the date changing, so a 08:00 reminder fires
 * once at 08:00 each day, not on every minute the command happens to run
 * within that same minute window.
 *
 * Needs `php artisan schedule:run` invoked every minute to actually fire
 * on time (see app/Console/Kernel.php) — unlike the once-a-day reminders
 * elsewhere in this app, a dosing alarm that only checks once a day
 * wouldn't be useful.
 *
 * Run it by hand any time with: php artisan medicines:send-dose-reminders
 */
class SendMedicineRemindersCommand extends Command
{
    protected $signature = 'medicines:send-dose-reminders';
    protected $description = 'Notifies patients whose medicine dosing reminder time is right now.';

    public function handle(NotificationService $notifications): int
    {
        $now = now();
        $currentTime = $now->format('H:i:00');
        $today = $now->toDateString();

        $dueReminders = MedicineReminderTime::whereTime('reminder_time', $currentTime)
            ->where(function ($q) use ($today) {
                $q->whereNull('last_sent_at')->orWhereDate('last_sent_at', '!=', $today);
            })
            ->with(['prescriptionItem.medicine', 'prescriptionItem.prescription.patient.account'])
            ->get()
            ->filter(fn (MedicineReminderTime $r) => $r->prescriptionItem->isDosingActive());

        if ($dueReminders->isEmpty()) {
            $this->info('No medicine reminders due.');
            return self::SUCCESS;
        }

        foreach ($dueReminders as $reminder) {
            $item = $reminder->prescriptionItem;
            $patient = $item->prescription->patient;

            $dosageNote = $item->dosage ? " ({$item->dosage})" : '';

            $notifications->notify(
                $patient->account,
                'medicine_reminder',
                "Time to take: {$item->medicine->generic_name}{$dosageNote}",
                $item->prescription_item_id
            );

            $reminder->update(['last_sent_at' => $now]);
        }

        $this->info("Sent {$dueReminders->count()} medicine reminder(s).");
        return self::SUCCESS;
    }
}
