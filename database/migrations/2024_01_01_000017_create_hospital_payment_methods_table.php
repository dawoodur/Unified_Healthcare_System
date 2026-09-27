<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_payment_methods', function (Blueprint $table) {
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->string('account_details', 190)->nullable();
            $table->primary(['hospital_id', 'payment_method_id']);

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('payment_method_id')->on('payment_methods')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_payment_methods');
    }
};
