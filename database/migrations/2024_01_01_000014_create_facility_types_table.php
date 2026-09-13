<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_types', function (Blueprint $table) {
            $table->id('facility_type_id');
            $table->unsignedBigInteger('category_id');
            $table->string('name', 150);
            $table->string('unit_label', 50)->nullable();

            $table->foreign('category_id')->references('category_id')->on('facility_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_types');
    }
};
