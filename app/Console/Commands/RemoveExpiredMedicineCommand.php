<?php

namespace App\Console\Commands;

use App\Models\PharmacyMedicineStock;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The automated backstop for expired medicine: a pharmacist can remove an
 * expired batch by hand any time (PharmacyInventoryController::destroy()),
 * but this command sweeps up whatever's still sitting expired — past its
 * expiry_date, with quantity_available still greater than 0 — deletes it
 * for real, and notifies each affected pharmacy of exactly what was
 * removed. Scheduled to run daily — see app/Console/Kernel.php.
 *
 * Run it by hand any time with: php artisan medicines:remove-expired
 */
class RemoveExpiredMedicineCommand extends Command
{
    protected $signature = 'medicines:remove-expired';
    protected $description = 'Deletes expired pharmacy stock batches and notifies each affected pharmacy.';

    public function handle(NotificationService $notifications): int
    {
        $expiredBatches = PharmacyMedicineStock::where('expiry_date', '<', now()->toDateString())
            ->where('quantity_available', '>', 0)
            ->with(['pharmacy.account', 'medicine'])
            ->get()
            ->groupBy('pharmacy_id');

        if ($expiredBatches->isEmpty()) {
            $this->info('No expired stock to remove.');
            return self::SUCCESS;
        }

        foreach ($expiredBatches as $pharmacyId => $batches) {
            $pharmacy = $batches->first()->pharmacy;

            // One notification per pharmacy per run, listing every batch
            // removed — not one notification per batch, which would just
            // spam a pharmacist that let several things expire at once.
            $summary = $batches
                ->map(fn (PharmacyMedicineStock $b) => "{$b->medicine->generic_name} ({$b->quantity_available} unit(s), expired {$b->expiry_date->format('M j, Y')})")
                ->implode('; ');

            DB::transaction(function () use ($batches) {
                foreach ($batches as $batch) {
                    $batch->delete();
                }
            });

            $notifications->notify(
                $pharmacy->account,
                'medicine_expired',
                "{$batches->count()} expired batch(es) removed from your inventory: {$summary}"
            );

            $this->info("Pharmacy #{$pharmacyId}: removed {$batches->count()} expired batch(es).");
        }

        return self::SUCCESS;
    }
}
