<?php

namespace App\Console\Commands;

use App\Services\InboxService;
use Illuminate\Console\Command;

/**
 * Runs InboxService::sweepArchive() — hides a patient's inbox chat with a
 * doctor/hospital/pharmacy/delivery agent once nothing (no booked
 * appointment, no open facility booking/operation request, no in-progress
 * medicine order/delivery) still ties them together. A periodic sweep
 * rather than a hook on every individual "mark completed" action because
 * several of those have more than one live code path (e.g. an appointment
 * can be marked visited from either AppointmentController or
 * Api\Doctor\AppointmentController) — see InboxService's docblock.
 */
class ArchiveInactiveInboxConversationsCommand extends Command
{
    protected $signature = 'inbox:archive-inactive';

    protected $description = 'Archives inbox conversations between a patient and a doctor/hospital/pharmacy/delivery agent once no active booking or order ties them together anymore';

    public function handle(InboxService $inbox): int
    {
        $count = $inbox->sweepArchive();

        $this->info($count === 0 ? 'Nothing to archive.' : "Archived {$count} conversation(s).");

        return self::SUCCESS;
    }
}
