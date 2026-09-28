<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use Illuminate\Console\Command;

/**
 * Permanently deletes inbox conversations that ArchiveInactiveInboxConversationsCommand
 * archived at least a week ago — inbox_messages cascade-deletes with them
 * (see the inbox_messages migration's FK). Until this runs, an archived
 * conversation is still readable by an admin at Admin > Inbox history.
 */
class PurgeArchivedInboxConversationsCommand extends Command
{
    protected $signature = 'inbox:purge-archived {--days=7 : Delete conversations archived at least this many days ago}';

    protected $description = 'Permanently deletes inbox conversations (and their messages) archived a week or more ago';

    public function handle(): int
    {
        // Not falsiness: "--days=0" (purge everything archived so far) is a
        // legitimate request, same reasoning as PurgeConsultationChatCommand's --hours.
        $days = $this->option('days') !== null ? max(0, (int) $this->option('days')) : 7;
        $cutoff = now()->subDays($days);

        $ids = Conversation::whereNotNull('archived_at')->where('archived_at', '<=', $cutoff)->pluck('conversation_id');

        if ($ids->isEmpty()) {
            $this->info("Nothing to purge — no conversations archived {$days}+ days ago.");
            return self::SUCCESS;
        }

        Conversation::whereIn('conversation_id', $ids)->delete();

        $this->info("Purged {$ids->count()} conversation(s) archived {$days}+ days ago.");

        return self::SUCCESS;
    }
}
