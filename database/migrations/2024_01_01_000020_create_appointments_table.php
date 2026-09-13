<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id('appointment_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->enum('appointment_type', ['online', 'onsite']);
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->unsignedInteger('serial_number');
            $table->enum('status', ['booked', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('booked');
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['doctor_id', 'appointment_date', 'serial_number'], 'uq_doctor_date_serial');
            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('set null');
            $table->foreign('template_id')->references('template_id')->on('doctor_availability_templates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
