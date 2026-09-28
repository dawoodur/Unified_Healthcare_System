<?php

namespace App\Http\Controllers\Api\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyMedicineStock;
use Illuminate\Support\Facades\Auth;

/**
 * JSON twin of PharmacyInventoryController::index().
 *
 * Read-only: adding a batch and removing one both carry side effects the
 * existing web routes already own (stock adjustments, and the "Done" action
 * that only happens once the pharmacist has physically pulled expired stock
 * off the shelf), so the React page links out to those rather than a second
 * code path behind the API.
 */
class InventoryController extends Controller
{
    public function index()
    {
        $pharmacy = Auth::user()->pharmacy;

        // Ordered by expiry_date so anything already expired (the earliest
        // dates) naturally sorts to the top. Nothing is deleted
        // automatically: an expired row on screen does not mean the physical
        // stock has been pulled yet.
        $grouped = $pharmacy->medicineStock()
            ->with('medicine')
            ->orderBy('expiry_date')
            ->get()
            ->groupBy('medicine_master_id');

        $medicines = $grouped->map(function ($batches) {
            $first = $batches->first();

            return [
                'medicine_master_id' => (int) $first->medicine_master_id,
                'generic_name' => $first->medicine?->generic_name,
                'brand_name' => $first->medicine?->brand_name,
                'form' => $first->medicine?->form,
                'strength' => $first->medicine?->strength,
                'total_quantity' => (int) $batches->sum('quantity_available'),
                'has_expired' => $batches->contains(fn (PharmacyMedicineStock $s) => $s->isExpired()),
                'batches' => $batches->map(fn (PharmacyMedicineStock $s) => [
                    'stock_id' => (int) $s->stock_id,
                    'batch_no' => $s->batch_no,
                    'expiry_label' => $s->expiry_date?->format('M j, Y'),
                    'is_expired' => $s->isExpired(),
                    'unit_price' => $s->unit_price,
                    'quantity_available' => (int) $s->quantity_available,
                ])->values(),
            ];
        })->values()->sortBy('generic_name')->values();

        return response()->json([
            'medicines' => $medicines,
            'totals' => [
                'medicines' => $medicines->count(),
                'batches' => $grouped->flatten()->count(),
                'expired_batches' => $grouped->flatten()->filter(fn (PharmacyMedicineStock $s) => $s->isExpired())->count(),
            ],
        ]);
    }
}
