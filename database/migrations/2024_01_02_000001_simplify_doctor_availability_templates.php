<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Switches doctor_availability_templates from "many small time slots within
 * a range" (start_time/end_time split into slot_duration_minutes chunks,
 * each with its own capacity_per_slot) to a simpler, more realistic model:
 * ONE fixed visiting window per day (e.g. "10:00 to 12:00"), with a single
 * total patient quota for that whole window. Patients booking into it get
 * a queue serial number instead of a personal time slot — matching how a
 * real doctor's chamber actually runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_availability_templates', function (Blueprint $table) {
            $table->dropColumn('slot_duration_minutes');
            $table->renameColumn('capacity_per_slot', 'max_patients');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_availability_templates', function (Blueprint $table) {
            $table->renameColumn('max_patients', 'capacity_per_slot');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(15)->after('end_time');
        });
    }
};
