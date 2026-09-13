<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A hospital facility (an MRI machine, an ICU ward, etc.) can only serve so
 * many patients a day — this adds that daily quota, the same idea as a
 * doctor's max_patients on DoctorAvailabilityTemplate, so facilities can be
 * booked online the same way appointments are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_facilities', function (Blueprint $table) {
            $table->unsignedSmallInteger('daily_capacity')->default(10)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('hospital_facilities', function (Blueprint $table) {
            $table->dropColumn('daily_capacity');
        });
    }
};
