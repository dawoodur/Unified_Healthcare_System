<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the last trace of recorded consultation chat.
 *
 * The in-call chat became ephemeral in
 * 2026_09_17_000006_make_consultation_chat_ephemeral: it now lives in the cache
 * for the duration of the call and is destroyed at hangup (see
 * ConsultationChatService), and that migration already emptied this table.
 * Nothing has written to it since, so an empty `chat_messages` table sitting in
 * the schema only invites the question "so where is the chat kept?" — the
 * answer being nowhere, which the schema should say too.
 *
 * Reversible: down() recreates the original table exactly as
 * 2024_01_01_000032_create_chat_messages_table defined it. It comes back empty,
 * which is no loss — the rows it used to hold were deleted by the previous
 * migration, and no conversation has been stored since.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('chat_messages');
    }

    public function down(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('sender_account_id');
            $table->text('message_text');
            $table->dateTime('sent_at')->useCurrent();
            $table->boolean('is_read')->default(false);

            $table->foreign('session_id')->references('session_id')->on('consultation_sessions')->onDelete('cascade');
            $table->foreign('sender_account_id')->references('account_id')->on('accounts')->onDelete('cascade');
            $table->index(['session_id', 'message_id'], 'idx_chat_session_id');
        });
    }
};
