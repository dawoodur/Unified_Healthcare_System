<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_calendar_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->date('note_date');
            $table->text('note');
            $table->timestamps();

            $table->unique(['doctor_id', 'note_date']);
            $table->foreign('doctor_id')
                ->references('doctor_id')
                ->on('doctors')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_calendar_notes');
    }
};
