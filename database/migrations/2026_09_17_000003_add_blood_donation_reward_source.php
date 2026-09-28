<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lets reward points be earned for donating blood: adds the ledger source
 * and the setting that says how many points a donation is worth (10).
 *
 * The amount lives in app_settings like every other reward number (see
 * RewardPointService), so it can be changed without touching code.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reward_point_ledger MODIFY source_type ENUM('review','medicine_purchase','lab_test_purchase','blood_donation','redemption','admin_adjustment') NOT NULL");

        DB::table('app_settings')->updateOrInsert(
            ['setting_key' => 'POINTS_PER_BLOOD_DONATION'],
            ['setting_value' => '10']
        );
    }

    public function down(): void
    {
        DB::table('reward_point_ledger')->where('source_type', 'blood_donation')->delete();
        DB::statement("ALTER TABLE reward_point_ledger MODIFY source_type ENUM('review','medicine_purchase','lab_test_purchase','redemption','admin_adjustment') NOT NULL");
        DB::table('app_settings')->where('setting_key', 'POINTS_PER_BLOOD_DONATION')->delete();
    }
};
