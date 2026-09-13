<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Caches each patient's most recent blood donation date — same pattern as
 * reward_points_balance: blood_donations is the real history/source of
 * truth, this is just a fast column to check "are they eligible to donate
 * (again) right now?" without scanning that whole table every time. Kept
 * in sync by BloodDonationService::recordDonation(), nowhere else.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE patients ADD COLUMN last_donated_at DATE NULL AFTER blood_group');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE patients DROP COLUMN last_donated_at');
    }
};
