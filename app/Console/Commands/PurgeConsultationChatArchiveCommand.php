<?php

namespace App\Console\Commands;

use App\Models\ConsultationChatArchive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Permanently deletes consultation chat archives (see
 * ConsultationChatService::archiveForAdmin()) 7+ days old — their photo
 * copies under storage/app/consultation-chat-archive/{archive_id}/ and
 * their consultation_chat_archive_messages rows go with them (the
 * messages via the table's own FK cascade; the photo directory removed
 * here, same as PurgeArchivedInboxConversationsCommand does for the
 * general inbox).
 */
class PurgeConsultationChatArchiveCommand extends Command
{
    protected $signature = 'consultations:purge-chat-archive {--days=7 : Delete archives at least this many days old}';

    protected $description = 'Permanently deletes admin-only consultation chat archives (and their shared photos) 7+ days old';

    public function handle(): int
    {
        $days = $this->option('days') !== null ? max(0, (int) $this->option('days')) : 7;
        $cutoff = now()->subDays($days);

        $archives = ConsultationChatArchive::where('archived_at', '<=', $cutoff)->get(['archive_id']);

        if ($archives->isEmpty()) {
            $this->info("Nothing to purge — no consultation chat archives {$days}+ days old.");
            return self::SUCCESS;
        }

        $disk = Storage::disk('local');
        foreach ($archives as $archive) {
            $disk->deleteDirectory('consultation-chat-archive/' . $archive->archive_id);
        }

        ConsultationChatArchive::whereIn('archive_id', $archives->pluck('archive_id'))->delete();

        $this->info("Purged {$archives->count()} consultation chat archive(s) {$days}+ days old.");

        return self::SUCCESS;
    }
}
