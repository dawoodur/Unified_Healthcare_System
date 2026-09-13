<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id('pharmacy_id');
            $table->unsignedBigInteger('account_id')->unique();
            $table->string('pharmacy_name', 190);
            $table->string('etin_number', 100)->unique();
            $table->string('address', 255)->nullable();

            $table->foreign('account_id')->references('account_id')->on('accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
    }
};
