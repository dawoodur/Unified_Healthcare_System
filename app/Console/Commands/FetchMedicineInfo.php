<?php

namespace App\Console\Commands;

use App\Models\MedicineGenericInfo;
use App\Models\MedicineInfoDraft;
use App\Services\MedicineInfoFetcher;
use Illuminate\Console\Command;

/**
 * Looks up medicines that still need proper information on openFDA and saves
 * what it finds as drafts for an admin to approve (Admin → Medicine drafts).
 *
 * Run it whenever you have internet — it is never triggered by a patient's
 * chat, so the assistant keeps working offline. Nothing it downloads reaches
 * a patient until a human approves it.
 */
class FetchMedicineInfo extends Command
{
    protected $signature = 'medicines:fetch-info {--limit=20 : How many generics to look up in one run}
                                                 {--generic= : Look up one specific generic name}';
    protected $description = 'Fetch medicine information drafts from openFDA for admin review';

    public function handle(MedicineInfoFetcher $fetcher): int
    {
        $targets = $this->option('generic')
            ? collect([mb_strtolower(trim($this->option('generic')))])
            : MedicineGenericInfo::where(fn ($q) => $q->where('needs_review', true)->orWhereNull('uses_en'))
                ->whereNotIn('generic_name', MedicineInfoDraft::where('status', 'pending')->pluck('generic_name'))
                ->limit((int) $this->option('limit'))
                ->pluck('generic_name');

        if ($targets->isEmpty()) {
            $this->info('Nothing to look up — every medicine either has reviewed information or a draft already waiting.');

            return self::SUCCESS;
        }

        $found = 0;
        $missing = [];

        foreach ($targets as $generic) {
            $draft = $fetcher->fetchAndStore($generic);

            if ($draft) {
                $found++;
                $this->line(sprintf('  %-22s found (queried as "%s")', $generic, $draft->queried_as));
            } else {
                $missing[] = $generic;
                $this->line(sprintf('  %-22s <comment>nothing usable</comment>', $generic));
            }

            // Be polite to a free public API, and keep runs slow enough to
            // notice if something is wrong.
            usleep(600000);
        }

        $this->newLine();
        $this->info("{$found} draft(s) saved for review at Admin → Medicine drafts.");

        if ($missing) {
            $this->warn('No US label matched: ' . implode(', ', $missing) . '. These need a human to write the information.');
        }

        return self::SUCCESS;
    }
}
