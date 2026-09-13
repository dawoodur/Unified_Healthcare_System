<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id('prescription_item_id');
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('medicine_master_id');
            $table->string('dosage', 100)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->string('notes', 255)->nullable();

            $table->foreign('prescription_id')->references('prescription_id')->on('prescriptions')->onDelete('cascade');
            $table->foreign('medicine_master_id')->references('medicine_master_id')->on('medicine_master');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
