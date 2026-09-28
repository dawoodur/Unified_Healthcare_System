<?php

namespace App\Console\Commands;

use App\Services\ConsultationChatService;
use Illuminate\Console\Command;

/**
 * Deletes photos left behind by consultation calls that were never properly
 * hung up (a crashed browser, a closed tab).
 *
 * In-call chat text needs no sweeping — it lives in the cache with a TTL and
 * expires by itself. Files on disk have no TTL, so they need this.
 */
class PurgeConsultationChatCommand extends Command
{
    protected $signature = 'consultations:purge-chat {--hours= : Delete photo folders untouched for this many hours}';

    protected $description = 'Deletes shared photos left over from consultation calls that never ended cleanly';

    public function handle(ConsultationChatService $chat): int
    {
        // Check for null, not falsiness: "--hours=0" (purge everything now) is
        // a legitimate request, and `?:` would silently treat that "0" as
        // "not given" and fall back to the default instead.
        $hours = $this->option('hours') !== null
            ? max(0, (int) $this->option('hours'))
            : ConsultationChatService::TTL_HOURS;
        $cutoff = now()->subHours($hours);

        $removed = $chat->purgePhotosOlderThan($cutoff);

        $this->info($removed === 0
            ? "Nothing to purge — no consultation photos older than {$hours}h."
            : "Purged {$removed} abandoned consultation photo folder(s) older than {$hours}h.");

        return self::SUCCESS;
    }
}
