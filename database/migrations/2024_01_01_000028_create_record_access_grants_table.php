<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_access_grants', function (Blueprint $table) {
            $table->id('grant_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('otp_id')->nullable();
            $table->enum('status', ['requested', 'otp_sent', 'approved', 'denied', 'expired'])->default('requested');
            $table->dateTime('requested_at')->useCurrent();
            $table->dateTime('granted_at')->nullable();
            $table->dateTime('expires_at')->nullable();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
            $table->foreign('otp_id')->references('otp_id')->on('otp_verifications')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_access_grants');
    }
};
