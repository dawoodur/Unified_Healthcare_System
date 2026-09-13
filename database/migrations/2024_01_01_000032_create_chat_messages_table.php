<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
