<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_hospital_applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('hospital_id');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->timestamps();

            $table->unique(['doctor_id', 'hospital_id']);
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_hospital_applications');
    }
};
