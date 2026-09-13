<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds gender (male/female) to the three role tables that represent an
 * individual person — patients, doctors, delivery agents. Hospitals and
 * pharmacies are business accounts (no personal name/age fields already),
 * and admins aren't publicly registered, so none of those three get this
 * column. DEFAULT 'male' lets this run cleanly against the existing
 * populated tables; every existing demo row gets overwritten with a real
 * value right after (see DemoDataSeeder / the backfill that follows this
 * migration), and every new registration sets it explicitly anyway.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE patients ADD COLUMN gender ENUM('male', 'female') NOT NULL DEFAULT 'male' AFTER blood_group");
        DB::statement("ALTER TABLE doctors ADD COLUMN gender ENUM('male', 'female') NOT NULL DEFAULT 'male' AFTER blood_group");
        DB::statement("ALTER TABLE delivery_agents ADD COLUMN gender ENUM('male', 'female') NOT NULL DEFAULT 'male' AFTER blood_group");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE patients DROP COLUMN gender');
        DB::statement('ALTER TABLE doctors DROP COLUMN gender');
        DB::statement('ALTER TABLE delivery_agents DROP COLUMN gender');
    }
};
