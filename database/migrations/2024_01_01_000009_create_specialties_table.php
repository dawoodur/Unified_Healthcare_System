<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $table) {
            $table->id('specialty_id');
            $table->string('specialty_name', 120)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specialties');
    }
};
