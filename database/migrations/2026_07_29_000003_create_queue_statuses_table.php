<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = "as of right now, this doctor is currently serving serial
 * number N, on this date" — a live waiting-room status a doctor updates
 * by hand as they see patients, so everyone else waiting can see roughly
 * how much longer until their own serial number. One row per doctor per
 * date (upserted, not appended to), since only the CURRENT number matters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_statuses', function (Blueprint $table) {
            $table->id('queue_status_id');
            $table->unsignedBigInteger('doctor_id');
            $table->date('queue_date');
            $table->unsignedInteger('current_serial')->default(0);
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['doctor_id', 'queue_date']);
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_statuses');
    }
};
