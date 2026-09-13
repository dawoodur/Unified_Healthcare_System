<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Some facilities are a one-day service (an X-Ray, a blood test — you show
 * up, it happens, the slot is free again for someone else the same day).
 * Others are an occupancy resource (an ICU bed, a Cabin — once a patient is
 * admitted, that bed is theirs until the hospital discharges them; it does
 * NOT free up again the next day just because a new day started). This
 * flag tells FacilityBookingService which capacity rule to apply — see
 * upcomingDates()/book() for the day-based rule vs
 * currentAvailability()/bookOccupancy() for the occupancy rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_types', function (Blueprint $table) {
            $table->boolean('is_occupancy')->default(false)->after('unit_label');
        });
    }

    public function down(): void
    {
        Schema::table('facility_types', function (Blueprint $table) {
            $table->dropColumn('is_occupancy');
        });
    }
};
