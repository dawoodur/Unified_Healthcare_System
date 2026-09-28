<?php

namespace App\Http\Controllers\Api\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PharmacyOrderController as WebPharmacyOrderController;
use App\Models\MedicineOrder;
use Illuminate\Support\Facades\Auth;

/**
 * JSON twin of PharmacyOrderController::index().
 *
 * Read-only: accepting or cancelling an order moves stock and notifies the
 * patient, so those stay on the existing web routes and the React page
 * posts to them — one code path for the side effects.
 */
class OrderController extends Controller
{
    public function index()
    {
        $pharmacyId = Auth::user()->pharmacy->pharmacy_id;
        $with = ['patient', 'items.medicine', 'deliveryAgent'];
        $pendingStatuses = WebPharmacyOrderController::PENDING_STATUSES;

        $pending = MedicineOrder::where('pharmacy_id', $pharmacyId)
            ->whereIn('status', $pendingStatuses)
            ->with($with)->orderBy('created_at')->get();

        $completed = MedicineOrder::where('pharmacy_id', $pharmacyId)
            ->whereNotIn('status', $pendingStatuses)
            ->with($with)->orderBy('created_at')->get();

        return response()->json([
            'pending' => $pending->map(fn (MedicineOrder $o) => $this->shape($o)),
            'completed' => $completed->map(fn (MedicineOrder $o) => $this->shape($o)),
        ]);
    }

    private function shape(MedicineOrder $order): array
    {
        return [
            'order_id' => (int) $order->order_id,
            'patient_name' => $order->patient?->full_name,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'delivery_address' => $order->delivery_address,
            'delivery_agent' => $order->deliveryAgent?->full_name,
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'placed_label' => $order->created_at?->format('M j, Y g:i A'),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->medicine?->generic_name,
                'brand' => $item->medicine?->brand_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => $item->unit_price_snapshot,
            ])->values(),
        ];
    }
}
