<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Occupancy facilities (ICU/CCU/General Ward beds, Cabins) are priced "per
 * day" but previously had no way for the patient to say how many days they
 * expect to stay — this column lets them pick that at booking time. Stays
 * null for non-occupancy bookings (a blood test doesn't have a "days").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('requested_days')->nullable()->after('booking_date');
        });
    }

    public function down(): void
    {
        Schema::table('facility_bookings', function (Blueprint $table) {
            $table->dropColumn('requested_days');
        });
    }
};
