<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Guards against SendRefillRemindersCommand emailing the same patient
 * about the same medicine's course running out twice — same idea as
 * appointments.reminder_sent_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE prescription_items ADD COLUMN refill_reminder_sent_at DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE prescription_items DROP COLUMN refill_reminder_sent_at');
    }
};
