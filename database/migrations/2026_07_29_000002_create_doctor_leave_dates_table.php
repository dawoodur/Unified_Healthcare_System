<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = one specific calendar date a doctor has blocked off (a
 * vacation day, a conference, etc.) — AppointmentService excludes these
 * dates from upcomingWindows() and rejects book() on them, even though
 * the doctor's normal weekly availability_templates would otherwise
 * offer that day. Doesn't touch the templates themselves, so nothing
 * needs restoring when the leave date passes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_leave_dates', function (Blueprint $table) {
            $table->id('leave_id');
            $table->unsignedBigInteger('doctor_id');
            $table->date('leave_date');
            $table->string('reason', 255)->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['doctor_id', 'leave_date']);
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_leave_dates');
    }
};
