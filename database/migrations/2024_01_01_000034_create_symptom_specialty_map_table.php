<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('symptom_specialty_map', function (Blueprint $table) {
            $table->id('map_id');
            $table->string('keyword', 100);
            $table->unsignedBigInteger('specialty_id');
            $table->integer('weight')->default(1);

            $table->unique(['keyword', 'specialty_id'], 'uq_symptom_specialty');
            $table->foreign('specialty_id')->references('specialty_id')->on('specialties')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('symptom_specialty_map');
    }
};
