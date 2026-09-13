<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = one ongoing conversation between two accounts, regardless of
 * role (e.g. a patient and a pharmacy, or a hospital and a doctor) — the
 * "inbox" feature, separate from the appointment-specific consultation
 * chat. The two accounts are always stored low-ID-first/high-ID-second so
 * there's never more than one conversation row for the same pair — see
 * Conversation::between(), which is the only place this table gets a new
 * row inserted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id('conversation_id');
            $table->unsignedBigInteger('participant_low_id');
            $table->unsignedBigInteger('participant_high_id');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('last_message_at')->nullable();

            $table->unique(['participant_low_id', 'participant_high_id']);
            $table->foreign('participant_low_id')->references('account_id')->on('accounts')->onDelete('cascade');
            $table->foreign('participant_high_id')->references('account_id')->on('accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
