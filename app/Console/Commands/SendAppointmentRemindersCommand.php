<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Emails every patient with an appointment tomorrow (status still
 * booked/confirmed — a cancelled one obviously doesn't need a reminder).
 * reminder_sent_at guards against sending the same reminder twice if this
 * ever runs more than once in a day. Scheduled to run daily at 8am — see
 * app/Console/Kernel.php.
 *
 * Run it by hand any time with: php artisan appointments:send-reminders
 */
class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Emails a reminder to every patient with an appointment tomorrow.';

    public function handle(NotificationService $notifications): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $appointments = Appointment::where('appointment_date', $tomorrow)
            ->whereIn('status', ['booked', 'confirmed'])
            ->whereNull('reminder_sent_at')
            ->with(['patient.account', 'doctor', 'hospital'])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No appointments tomorrow that need a reminder.');
            return self::SUCCESS;
        }

        foreach ($appointments as $appointment) {
            try {
                Mail::to($appointment->patient->account->email)->send(new AppointmentReminderMail($appointment));
            } catch (\Throwable $e) {
                report($e);
            }

            $notifications->notify(
                $appointment->patient->account,
                'appointment_reminder',
                "Reminder: your appointment with Dr. {$appointment->doctor->full_name} is tomorrow at {$appointment->timeRangeLabel()}.",
                $appointment->appointment_id
            );

            $appointment->update(['reminder_sent_at' => now()]);
        }

        $this->info("Sent {$appointments->count()} reminder(s).");
        return self::SUCCESS;
    }
}
