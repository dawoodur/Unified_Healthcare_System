<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_availability_templates', function (Blueprint $table) {
            $table->id('template_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedTinyInteger('day_of_week')->comment('0=Sunday .. 6=Saturday');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(15);
            $table->enum('mode', ['online', 'onsite']);
            $table->unsignedSmallInteger('capacity_per_slot')->default(1);
            $table->boolean('is_active')->default(true);

            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_availability_templates');
    }
};
