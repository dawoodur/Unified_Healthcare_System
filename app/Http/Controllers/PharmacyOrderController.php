<?php

namespace App\Http\Controllers;

use App\Models\MedicineOrder;
use App\Services\MedicineOrderService;
use Illuminate\Support\Facades\Auth;

/**
 * A pharmacy's side of order fulfillment: see every order placed with
 * them, accept it (stock was already reserved when the patient placed
 * it — see MedicineOrderService::placeOrder()), or cancel it and put the
 * reserved stock back.
 */
class PharmacyOrderController extends Controller
{
    public function __construct(private MedicineOrderService $orders)
    {
    }

    // Same idea as AppointmentController::PENDING_STATUSES. Public because
    // the React pharmacy section's Api\Pharmacy\OrderController splits its
    // list the same way, and a second copy of this would drift.
    public const PENDING_STATUSES = ['placed', 'accepted', 'out_for_delivery'];

    /** Shows every order placed with this pharmacy, split into Pending/Completed and sorted earliest-placed first (GET /pharmacy/orders). */
    public function index()
    {
        $pharmacyId = Auth::user()->pharmacy->pharmacy_id;
        $with = ['patient', 'items.medicine', 'deliveryAgent'];

        $pending = MedicineOrder::where('pharmacy_id', $pharmacyId)
            ->whereIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('created_at')->get();

        $completed = MedicineOrder::where('pharmacy_id', $pharmacyId)
            ->whereNotIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('created_at')->get();

        return view('pharmacy.orders', compact('pending', 'completed'));
    }

    /** Accepts a just-placed order, signalling the pharmacy will prepare it (POST /pharmacy/orders/{order}/accept). */
    public function accept(MedicineOrder $order)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'placed') {
            return back()->withErrors(['status' => 'This order is no longer pending.']);
        }

        $order->update(['status' => 'accepted']);

        return back()->with('success', 'Order accepted.');
    }

    /** Cancels an order and restores its reserved stock (POST /pharmacy/orders/{order}/cancel). */
    public function cancel(MedicineOrder $order)
    {
        $this->authorizeOrder($order);

        if (!in_array($order->status, ['placed', 'accepted'], true)) {
            return back()->withErrors(['status' => 'This order can no longer be cancelled.']);
        }

        $this->orders->cancelOrder($order);

        return back()->with('success', 'Order cancelled and stock restored.');
    }

    private function authorizeOrder(MedicineOrder $order): void
    {
        if ($order->pharmacy_id !== Auth::user()->pharmacy->pharmacy_id) {
            abort(403);
        }
    }
}
