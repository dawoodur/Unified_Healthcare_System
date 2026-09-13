<?php

namespace App\Console\Commands;

use App\Mail\RefillReminderMail;
use App\Models\PrescriptionItem;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Emails a patient the day before a prescribed medicine's course
 * (issued_at + duration_days) runs out, prompting them to reorder.
 * refill_reminder_sent_at guards against sending it twice. Only items
 * with a duration_days actually set are considered — an open-ended
 * prescription item has nothing to count down from. Scheduled to run
 * daily — see app/Console/Kernel.php.
 *
 * Run it by hand any time with: php artisan prescriptions:send-refill-reminders
 */
class SendRefillRemindersCommand extends Command
{
    protected $signature = 'prescriptions:send-refill-reminders';
    protected $description = "Emails a patient the day before a prescribed medicine's course runs out.";

    public function handle(NotificationService $notifications): int
    {
        $tomorrow = now()->addDay()->toDateString();

        // Filtered in PHP (not a SQL DATE_ADD join) — prescription_items
        // is small for a demo-scale app, and this keeps the "which date
        // does this item run out on" math in one obvious place instead
        // of split across a raw SQL expression and PHP.
        $dueItems = PrescriptionItem::whereNotNull('duration_days')
            ->whereNull('refill_reminder_sent_at')
            ->with(['prescription.patient.account', 'prescription.doctor', 'medicine'])
            ->get()
            ->filter(function (PrescriptionItem $item) use ($tomorrow) {
                $finishDate = $item->prescription->issued_at->copy()->addDays($item->duration_days)->toDateString();
                return $finishDate === $tomorrow;
            });

        if ($dueItems->isEmpty()) {
            $this->info('No refill reminders due.');
            return self::SUCCESS;
        }

        foreach ($dueItems as $item) {
            $patient = $item->prescription->patient;

            try {
                Mail::to($patient->account->email)->send(new RefillReminderMail($item));
            } catch (\Throwable $e) {
                report($e);
            }

            $notifications->notify(
                $patient->account,
                'refill_reminder',
                "Your course of {$item->medicine->generic_name} is about to run out — reorder if you still need it.",
                $item->prescription_item_id
            );

            $item->update(['refill_reminder_sent_at' => now()]);
        }

        $this->info("Sent {$dueItems->count()} refill reminder(s).");
        return self::SUCCESS;
    }
}
