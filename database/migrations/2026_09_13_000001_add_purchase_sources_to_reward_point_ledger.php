<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reward_point_ledger MODIFY source_type ENUM('review','medicine_purchase','lab_test_purchase','redemption','admin_adjustment') NOT NULL");
    }

    public function down(): void
    {
        DB::table('reward_point_ledger')
            ->whereIn('source_type', ['medicine_purchase', 'lab_test_purchase'])
            ->delete();

        DB::statement("ALTER TABLE reward_point_ledger MODIFY source_type ENUM('review','redemption','admin_adjustment') NOT NULL");
    }
};
