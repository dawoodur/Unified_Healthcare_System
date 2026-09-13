<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = one hospital's emergency call for a specific blood type — see
 * BloodDonationService::sendEmergencyRequest(). recipient_count is a
 * snapshot of how many patients were actually emailed at the moment this
 * was sent (not a live count), so the hospital can see the reach of a
 * past request even after more patients register or donate later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_requests', function (Blueprint $table) {
            $table->id('blood_request_id');
            $table->unsignedBigInteger('hospital_id');
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']);
            $table->string('message', 500)->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_requests');
    }
};
