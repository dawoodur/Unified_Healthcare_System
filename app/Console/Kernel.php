<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Sweeps expired pharmacy stock and notifies each affected
        // pharmacy — see RemoveExpiredMedicineCommand. Runs once a day;
        // for this to actually fire on its own, something needs to call
        // `php artisan schedule:run` every minute (Laravel checks each
        // due task itself) — on Windows that's a Task Scheduler entry,
        // not a real cron daemon. See SETUP_ON_NEW_PC.md.
        $schedule->command('medicines:remove-expired')->daily();

        // Appointment reminder emails — see SendAppointmentRemindersCommand.
        $schedule->command('appointments:send-reminders')->dailyAt('08:00');

        // Prescription refill reminders — see SendRefillRemindersCommand.
        $schedule->command('prescriptions:send-refill-reminders')->dailyAt('08:00');

        // "Time to take your medicine" dosing alarms — see
        // SendMedicineRemindersCommand. Runs every minute (not daily like
        // the reminders above) since a dosing alarm needs to fire at the
        // specific time of day a patient chose, not once each morning.
        $schedule->command('medicines:send-dose-reminders')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
