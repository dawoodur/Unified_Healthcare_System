<?php

namespace App\Http\Controllers;

use App\Models\MedicineMaster;
use App\Services\MedicineInfoService;
use App\Models\PharmacyMedicineStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a pharmacy manage its own medicine stock: which medicines it
 * carries, in which batches, at what price, expiring when, and how many
 * units are left. This table (pharmacy_medicine_stock) is what powers
 * the patient-facing price comparison — see MedicineComparisonController.
 */
class PharmacyInventoryController extends Controller
{
    /** Shows this pharmacy's current stock + a form to add/update a batch (GET /pharmacy/inventory). */
    public function index()
    {
        $pharmacy = Auth::user()->pharmacy;

        // Ordered by expiry_date, so anything already expired (the
        // earliest dates) naturally sorts to the top — see
        // PharmacyMedicineStock::isExpired() for the flag the view uses
        // to show it clearly. Nothing here deletes it automatically: an
        // expired row on a screen doesn't mean the physical stock has
        // actually been pulled off the shelf yet, so it stays listed
        // until the pharmacist clicks "Done" after actually removing it
        // — see destroy() below, the same action either way.
        $stockByMedicine = $pharmacy->medicineStock()
            ->orderBy('expiry_date')
            ->get()
            ->groupBy('medicine_master_id');

        return view('pharmacy.inventory', compact('stockByMedicine'));
    }

    /**
     * Shows the "add or update a batch" form (GET /pharmacy/inventory/create).
     * ?edit={stock_id} pre-fills it from an existing batch of this
     * pharmacy's own stock, for the "Edit" link on the inventory list.
     */
    public function create(Request $request)
    {
        $medicines = MedicineMaster::orderBy('generic_name')->get();

        $editing = null;
        if ($request->filled('edit')) {
            $editing = PharmacyMedicineStock::where('pharmacy_id', Auth::user()->pharmacy->pharmacy_id)
                ->find($request->integer('edit'));
        }

        return view('pharmacy.inventory-create', compact('medicines', 'editing'));
    }

    /**
     * Adds (or updates) one batch of one medicine (POST /pharmacy/inventory).
     * A "batch" is identified by medicine + batch number — the same batch
     * number submitted again just updates that row instead of duplicating it.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'medicine_master_id' => ['required', 'integer', 'exists:medicine_master,medicine_master_id'],
            'batch_no' => ['required', 'string', 'max:60'],
            'expiry_date' => ['required', 'date', 'after:today'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'quantity_available' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        $pharmacy = Auth::user()->pharmacy;

        PharmacyMedicineStock::updateOrCreate(
            [
                'pharmacy_id' => $pharmacy->pharmacy_id,
                'medicine_master_id' => $data['medicine_master_id'],
                'batch_no' => $data['batch_no'],
            ],
            [
                'expiry_date' => $data['expiry_date'],
                'unit_price' => $data['unit_price'],
                'quantity_available' => $data['quantity_available'],
            ]
        );

        return redirect()->route('pharmacy.inventory')->with('success', 'Stock saved.');
    }

    /** Removes one batch listing entirely (POST /pharmacy/inventory/{stock}/remove). */
    public function destroy(PharmacyMedicineStock $stock)
    {
        if ($stock->pharmacy_id !== Auth::user()->pharmacy->pharmacy_id) {
            abort(403);
        }

        $stock->delete();

        return back()->with('success', 'Removed.');
    }

    /** Shows the "add a new medicine to the catalog" form (GET /pharmacy/inventory/medicines/create). */
    public function createMedicine()
    {
        return view('pharmacy.medicines-create');
    }

    /**
     * Adds a brand-new medicine to the shared catalog (POST
     * /pharmacy/inventory/medicines) — any pharmacy can do this, for
     * whenever the medicine they want to stock isn't in the dropdown yet.
     * There's no admin approval step; this is deliberately simple, matching
     * how the rest of this app's "one shared catalog, every seller sets
     * their own price/stock against it" tables work (facility_types is the
     * same idea, just seeded once instead of pharmacy-added). To avoid two
     * pharmacies creating near-duplicate rows for the same drug, this
     * checks for an existing case-insensitive match on generic+brand name
     * first and reuses that instead of creating a new one.
     */
    public function storeMedicine(Request $request)
    {
        $data = $request->validate([
            'generic_name' => ['required', 'string', 'max:150'],
            'brand_name' => ['nullable', 'string', 'max:150'],
            'form' => ['nullable', 'string', 'max:60'],
            'strength' => ['nullable', 'string', 'max:60'],
        ]);

        $existing = MedicineMaster::whereRaw('LOWER(generic_name) = ?', [strtolower($data['generic_name'])])
            ->where(function ($query) use ($data) {
                $brand = $data['brand_name'] ?? null;
                $brand === null
                    ? $query->whereNull('brand_name')
                    : $query->whereRaw('LOWER(brand_name) = ?', [strtolower($brand)]);
            })
            ->first();

        if ($existing) {
            return redirect()->route('pharmacy.inventory.create')->with('success', "\"{$existing->generic_name}\" is already in the catalog — select it below to stock it.");
        }

        $medicine = MedicineMaster::create($data);

        // Make the health chat able to answer "what is this for?" about the new
        // medicine straight away: an existing generic already has its entry, a
        // new one gets a class-level entry worked out from its name and flagged
        // for an admin to confirm. See MedicineInfoService.
        app(MedicineInfoService::class)->forMedicine($medicine);

        return redirect()->route('pharmacy.inventory.create')->with('success', 'Medicine added to the catalog — you can now stock it.');
    }
}
