<?php

namespace App\Console\Commands;

use App\Models\MedicineGenericInfo;
use App\Services\MedicineInfoService;
use Illuminate\Console\Command;

/**
 * Gives every generic in the catalog an info entry so the health chat can
 * answer about it. Pharmacies adding a medicine through the app trigger this
 * automatically (PharmacyInventoryController::storeMedicine); this command
 * covers rows that arrived any other way — seeders, imports, direct SQL.
 */
class SyncMedicineInfo extends Command
{
    protected $signature = 'medicines:sync-info';
    protected $description = 'Ensure every catalog generic has medicine information for the health chat';

    public function handle(MedicineInfoService $info): int
    {
        $result = $info->syncCatalog();

        $this->info(sprintf(
            '%d new generic(s): %d described from their drug class, %d unrecognised.',
            $result['created'],
            $result['inferred'],
            $result['unknown']
        ));

        $review = MedicineGenericInfo::where('needs_review', true)->count();
        if ($review > 0) {
            $this->warn("{$review} generic(s) still need a human to confirm or write their information (Admin → Medicine information).");
        }

        return self::SUCCESS;
    }
}
