<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webrtc_signals', function (Blueprint $table) {
            $table->id('signal_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('sender_account_id');
            $table->enum('signal_type', ['offer', 'answer', 'ice_candidate', 'hangup']);
            $table->text('payload');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('session_id')->references('session_id')->on('consultation_sessions')->onDelete('cascade');
            $table->foreign('sender_account_id')->references('account_id')->on('accounts')->onDelete('cascade');
            $table->index(['session_id', 'signal_id'], 'idx_signal_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webrtc_signals');
    }
};
