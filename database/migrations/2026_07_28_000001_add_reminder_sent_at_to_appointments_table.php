<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Marks when a reminder email was sent for an appointment, so the daily
 * SendAppointmentRemindersCommand never sends the same patient the same
 * reminder twice (e.g. if the scheduled task happens to run more than
 * once, or is re-run by hand while testing).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE appointments ADD COLUMN reminder_sent_at DATETIME NULL AFTER status');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE appointments DROP COLUMN reminder_sent_at');
    }
};
