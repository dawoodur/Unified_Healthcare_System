<?php

namespace App\Http\Controllers\Api\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\MedicineOrderItem;
use App\Models\PharmacyMedicineStock;
use Illuminate\Support\Facades\Auth;

/**
 * JSON twin of what DashboardController::pharmacy() used to render into
 * pharmacy/dashboard.blade.php — the queries are unchanged, they just
 * serialise instead of feeding a Blade view (see resources/js/pharmacy/).
 */
class DashboardController extends Controller
{
    /**
     * A batch this shallow is worth flagging before it runs out — a fixed,
     * documented threshold, same as the old Blade dashboard used.
     */
    private const LOW_STOCK_THRESHOLD = 20;

    public function index()
    {
        $pharmacy = Auth::user()->pharmacy;
        $pharmacyId = $pharmacy->pharmacy_id;

        $totalMedicines = $pharmacy->medicineStock()->distinct('medicine_master_id')->count('medicine_master_id');
        $lowStockCount = $pharmacy->medicineStock()
            ->where('quantity_available', '>', 0)
            ->where('quantity_available', '<=', self::LOW_STOCK_THRESHOLD)
            ->count();
        $todayOrdersCount = $pharmacy->orders()->whereDate('created_at', now()->toDateString())->count();
        $todaySales = $pharmacy->orders()
            ->whereDate('created_at', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $outOfStockCount = $pharmacy->medicineStock()->where('quantity_available', 0)->count();
        $inStockCount = $pharmacy->medicineStock()->where('quantity_available', '>', self::LOW_STOCK_THRESHOLD)->count();

        $expiring = $pharmacy->medicineStock()
            ->with('medicine')
            ->where('expiry_date', '<=', now()->addDays(30)->toDateString())
            ->orderBy('expiry_date')
            ->take(8)
            ->get()
            ->map(fn (PharmacyMedicineStock $stock) => [
                'stock_id' => (int) $stock->stock_id,
                'medicine' => $stock->medicine?->generic_name,
                'brand' => $stock->medicine?->brand_name,
                'batch_no' => $stock->batch_no,
                'expiry_label' => $stock->expiry_date?->format('M j, Y'),
                'is_expired' => $stock->isExpired(),
                'quantity' => (int) $stock->quantity_available,
            ]);

        $topMedicines = MedicineOrderItem::whereHas('order', fn ($q) => $q->where('pharmacy_id', $pharmacyId))
            ->selectRaw('medicine_master_id, SUM(quantity) as total_qty')
            ->groupBy('medicine_master_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->with('medicine')
            ->get()
            ->map(fn (MedicineOrderItem $row) => [
                'label' => $row->medicine->generic_name,
                'value' => (int) $row->total_qty,
                'formatted' => $row->total_qty . ' ' . __('dashboard.pharmacy.units_suffix'),
            ]);

        return response()->json([
            'pharmacy' => [
                'pharmacy_id' => (int) $pharmacyId,
                'pharmacy_name' => $pharmacy->pharmacy_name,
                'etin_number' => $pharmacy->etin_number,
                'address' => $pharmacy->address,
                'uid_tag' => Auth::user()->uidTag(),
            ],
            'stats' => [
                'total_medicines' => (int) $totalMedicines,
                'low_stock' => (int) $lowStockCount,
                'today_orders' => (int) $todayOrdersCount,
                'today_sales' => (float) $todaySales,
            ],
            'stock_overview' => [
                'in_stock' => (int) $inStockCount,
                'low_stock' => (int) $lowStockCount,
                'out_of_stock' => (int) $outOfStockCount,
                'threshold' => self::LOW_STOCK_THRESHOLD,
            ],
            'expiring' => $expiring,
            'top_medicines' => $topMedicines,
        ]);
    }
}
