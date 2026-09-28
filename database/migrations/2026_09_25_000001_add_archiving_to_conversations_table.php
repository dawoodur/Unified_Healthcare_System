<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a conversation be archived instead of deleted outright — see
 * InboxService::sweepArchive(). Archived conversations disappear from both
 * participants' inbox (and can no longer be opened or messaged — see
 * InboxController), but stay in the database so an admin can still read
 * them for a week (Admin > Inbox history) before
 * PurgeArchivedInboxConversationsCommand permanently deletes them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dateTime('archived_at')->nullable()->after('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
