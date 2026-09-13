<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A patient requesting a major operation (Surgery-category facility types
 * only — see OperationRequestController) is a negotiation, not an instant
 * booking like facility_bookings: the hospital reviews it and responds
 * with a doctor/date/time/serial number, the patient then accepts (and
 * pays) or declines. Kept as its own table rather than extending
 * facility_bookings because that table has no concept of a pending
 * request/offer, no doctor column, and no time column — bolting all of
 * that on would complicate the simple instant-booking flow it already
 * handles well for tests/ICU/etc.
 *
 * status lifecycle:
 *   requested -> offered -> accepted -> completed
 *                        \-> declined -> (hospital can re-offer -> offered)
 *   requested -> cancelled (patient withdraws before any offer)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_requests', function (Blueprint $table) {
            $table->id('operation_request_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('facility_type_id');
            $table->text('patient_notes')->nullable();

            // Filled in once the hospital makes/updates an offer.
            $table->unsignedBigInteger('assigned_doctor_id')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            // Not a strictly-enforced sequential counter (no unique index)
            // — deliberately just an editable prioritization number a
            // hospital can freely reassign for emergencies, closer to a
            // manual triage tool than facility_bookings' auto-incrementing
            // per-day serial.
            $table->unsignedInteger('serial_number')->nullable();
            // Snapshot of the facility type's price at request time (this
            // flow doesn't support the hospital custom-quoting a price —
            // see OperationRequestController).
            $table->decimal('price', 10, 2);

            $table->enum('status', ['requested', 'offered', 'accepted', 'declined', 'cancelled', 'completed'])->default('requested');

            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('offered_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->foreign('facility_type_id')->references('facility_type_id')->on('facility_types')->onDelete('cascade');
            $table->foreign('assigned_doctor_id')->references('doctor_id')->on('doctors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_requests');
    }
};
