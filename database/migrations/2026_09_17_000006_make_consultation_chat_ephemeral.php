<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the in-call consultation chat ephemeral.
 *
 * What the chat is for — showing a photo of a rash, typing a dosage while
 * talking — does not belong in a permanent medical record, so it is no longer
 * written to the database at all. It now lives in the cache for the duration
 * of the call and is destroyed when the call ends (see ConsultationChatService).
 *
 * Two things this migration does:
 *
 *  1. Adds the counts that the wrap-up summary needs. The summary never quoted
 *     the conversation — it only ever said how many messages were exchanged —
 *     so a count captured at hangup keeps it accurate without keeping any
 *     content. Nullable because sessions that ended before this change have no
 *     count to report, which the summary states plainly rather than guessing.
 *
 *  2. Deletes the chat rows already stored, so no recorded conversation
 *     survives this change. The rows are demo data; chat_messages itself is
 *     left in place (empty and no longer written to) rather than dropped, so
 *     this migration stays reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('chat_message_count')->nullable()->after('summary');
            $table->unsignedSmallInteger('photo_count')->nullable()->after('chat_message_count');
        });

        // Keep each finished session's summary honest: record how many
        // messages it had before the rows go away.
        foreach (DB::table('consultation_sessions')->select('session_id')->get() as $session) {
            $count = DB::table('chat_messages')->where('session_id', $session->session_id)->count();

            DB::table('consultation_sessions')
                ->where('session_id', $session->session_id)
                ->update(['chat_message_count' => $count, 'photo_count' => 0]);
        }

        // No conversation stays recorded.
        DB::table('chat_messages')->delete();
    }

    public function down(): void
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->dropColumn(['chat_message_count', 'photo_count']);
        });
    }
};
