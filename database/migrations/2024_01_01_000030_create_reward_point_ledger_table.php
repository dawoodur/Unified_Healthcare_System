<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_point_ledger', function (Blueprint $table) {
            $table->id('ledger_id');
            $table->unsignedBigInteger('patient_id');
            $table->integer('points');
            $table->enum('source_type', ['review', 'redemption', 'admin_adjustment']);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_point_ledger');
    }
};
