<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = one test/operation/procedure a doctor recommended as part of a
 * prescription (e.g. "CBC blood test", "Appendectomy") — the facility-type
 * equivalent of prescription_items (which is medicines only). Booking one
 * of these stays exactly as open as booking any other facility (see
 * FacilityBookingController) — this table is just a record of what the
 * doctor recommended, with a convenient link into the existing
 * compare-and-book flow, same idea as a prescribed medicine's
 * "Compare & Order" link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_facility_items', function (Blueprint $table) {
            $table->id('prescription_facility_item_id');
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('facility_type_id');
            $table->string('notes', 255)->nullable();

            $table->foreign('prescription_id')->references('prescription_id')->on('prescriptions')->onDelete('cascade');
            $table->foreign('facility_type_id')->references('facility_type_id')->on('facility_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_facility_items');
    }
};
