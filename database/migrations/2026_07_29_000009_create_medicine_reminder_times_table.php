<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Time to take your medicine" reminders — distinct from
 * prescription_items.refill_reminder_sent_at, which reminds a patient to
 * REORDER once a course is about to run out. This is the everyday dosing
 * alarm: a patient can set one or more times of day per prescribed
 * medicine (e.g. "twice daily" -> 08:00 and 20:00), and
 * SendMedicineRemindersCommand notifies them each time it comes around.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_reminder_times', function (Blueprint $table) {
            $table->id('reminder_time_id');
            $table->unsignedBigInteger('prescription_item_id');
            $table->time('reminder_time');
            $table->dateTime('last_sent_at')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('prescription_item_id')->references('prescription_item_id')->on('prescription_items')->onDelete('cascade');
            $table->index(['reminder_time', 'last_sent_at'], 'idx_reminder_due_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_reminder_times');
    }
};
