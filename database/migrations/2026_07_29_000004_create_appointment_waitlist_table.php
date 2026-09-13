<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = one patient asking to be told if a spot opens up in a
 * specific doctor's window on a specific date, because it was full when
 * they checked. See AppointmentService::joinWaitlist()/cancel() (the
 * latter is what actually frees a spot and triggers a notification —
 * see notifyNextWaitlisted()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_waitlist', function (Blueprint $table) {
            $table->id('waitlist_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('template_id');
            $table->date('requested_date');
            $table->enum('status', ['waiting', 'notified', 'cancelled'])->default('waiting');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('notified_at')->nullable();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
            $table->foreign('template_id')->references('template_id')->on('doctor_availability_templates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_waitlist');
    }
};
