<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row = one patient bookmarking one doctor for faster re-booking later. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_doctors', function (Blueprint $table) {
            $table->id('favorite_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['patient_id', 'doctor_id']);
            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_doctors');
    }
};
