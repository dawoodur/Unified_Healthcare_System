<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = one patient's booking for a hospital facility (an MRI, an ICU
 * bed, a blood test, etc.) on a given date — the facility equivalent of
 * `appointments`. Same shape, same idea: no personal time slot, just a
 * queue serial number for that hospital+facility+date (see
 * FacilityBookingService::book()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_bookings', function (Blueprint $table) {
            $table->id('facility_booking_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('facility_type_id');
            $table->date('booking_date');
            $table->unsignedInteger('serial_number');
            // Snapshot of the price at the moment of booking — if the
            // hospital changes its price later, this booking still shows
            // what the patient was actually quoted, same idea as
            // medicine_order_items.unit_price_snapshot.
            $table->decimal('price', 10, 2);
            $table->enum('status', ['booked', 'completed', 'cancelled'])->default('booked');
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['hospital_id', 'facility_type_id', 'booking_date', 'serial_number'], 'uq_facility_booking_serial');
            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->foreign('facility_type_id')->references('facility_type_id')->on('facility_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_bookings');
    }
};
