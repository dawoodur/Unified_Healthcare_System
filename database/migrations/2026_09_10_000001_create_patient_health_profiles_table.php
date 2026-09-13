<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_health_profiles', function (Blueprint $table) {
            $table->id('health_profile_id');
            $table->unsignedBigInteger('patient_id')->unique();
            $table->string('cholesterol_status', 100)->nullable();
            $table->string('diabetes_risk', 100)->nullable();
            $table->text('diet_notes')->nullable();
            $table->text('therapy_notes')->nullable();
            $table->text('major_health_risks')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->text('lifestyle_notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_health_profiles');
    }
};
