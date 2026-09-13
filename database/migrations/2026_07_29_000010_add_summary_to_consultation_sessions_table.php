<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Holds the auto-generated wrap-up text for a video consultation — see
 * ConsultationSummaryService. There's no server-side recording/transcript
 * of the actual call (WebRTC audio/video is peer-to-peer, never touches
 * this server), so this is built from what the server DOES have: the
 * in-room chat log, the diagnosis notes, and the medicines/tests just
 * prescribed — not a summary of spoken conversation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->dropColumn('summary');
        });
    }
};
