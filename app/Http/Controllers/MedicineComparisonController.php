<?php

namespace App\Http\Controllers;

use App\Models\MedicineMaster;
use App\Models\Pharmacy;
use App\Models\PharmacyMedicineStock;
use App\Services\MedicineOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The patient-facing side of medicine shopping: search the shared
 * medicine catalog, then (once one is picked) see every pharmacy that
 * currently has it in stock, cheapest first — the same "affordability
 * comparison" idea as FacilityComparisonController, but for medicine
 * instead of hospital facilities.
 */
class MedicineComparisonController extends Controller
{
    public function __construct(private MedicineOrderService $orders)
    {
    }

    /** Shows the medicine search + (once one is chosen) a price-sorted pharmacy list (GET /patient/medicine). */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $medicines = MedicineMaster::when($search !== '', function ($query) use ($search) {
                $query->where('generic_name', 'like', "%{$search}%")
                    ->orWhere('brand_name', 'like', "%{$search}%");
            })
            ->orderBy('generic_name')
            ->get();

        $selectedId = $request->integer('medicine_master_id') ?: null;
        $selectedMedicine = null;
        $offerings = collect();
        $isPrescribed = false;

        if ($selectedId) {
            $selectedMedicine = MedicineMaster::findOrFail($selectedId);

            // Ordering is gated on this — see CartController::add() for
            // the actual enforcement. Checked here too so the "Add to
            // cart" button can just not be shown instead of failing after
            // a wasted round trip.
            $isPrescribed = in_array($selectedId, Auth::user()->patient->prescribedMedicineIds(), true);

            // One row per pharmacy: its cheapest currently-in-stock,
            // non-expired batch price, and how many units it has across
            // all such batches combined.
            $offerings = PharmacyMedicineStock::where('medicine_master_id', $selectedId)
                ->where('quantity_available', '>', 0)
                ->where('expiry_date', '>', now()->toDateString())
                ->with('pharmacy')
                ->get()
                ->groupBy('pharmacy_id')
                ->map(fn ($batches) => (object) [
                    'pharmacy' => $batches->first()->pharmacy,
                    'price' => $batches->min('unit_price'),
                    'quantity_available' => $batches->sum('quantity_available'),
                ])
                ->sortBy('price')
                ->values();
        }

        $cart = session('cart', ['pharmacy_id' => null, 'items' => []]);
        $cartPharmacy = $cart['pharmacy_id'] ? Pharmacy::find($cart['pharmacy_id']) : null;
        $cartPreview = $cartPharmacy
            ? $this->orders->previewCart($cartPharmacy, $cart['items'])
            : ['lines' => collect(), 'subtotal' => 0];

        return view('patient.medicine', [
            'medicines' => $medicines,
            'search' => $search,
            'selectedMedicine' => $selectedMedicine,
            'offerings' => $offerings,
            'isPrescribed' => $isPrescribed,
            'cartPharmacy' => $cartPharmacy,
            'cartLines' => $cartPreview['lines'],
            'cartSubtotal' => $cartPreview['subtotal'],
        ]);
    }
}
