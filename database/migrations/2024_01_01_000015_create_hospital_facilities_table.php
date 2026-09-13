<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_facilities', function (Blueprint $table) {
            $table->id('facility_offering_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('facility_type_id');
            $table->decimal('price', 10, 2);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['hospital_id', 'facility_type_id']);
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->foreign('facility_type_id')->references('facility_type_id')->on('facility_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_facilities');
    }
};
