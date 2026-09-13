<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = one message inside one inbox conversation. Named
 * "inbox_messages" (not just "messages") so it's never confused with
 * chat_messages, which is the separate consultation-room chat tied to a
 * specific online appointment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_account_id');
            $table->text('message_text');
            $table->dateTime('sent_at')->useCurrent();
            $table->boolean('is_read')->default(false);

            $table->foreign('conversation_id')->references('conversation_id')->on('conversations')->onDelete('cascade');
            $table->foreign('sender_account_id')->references('account_id')->on('accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
    }
};
