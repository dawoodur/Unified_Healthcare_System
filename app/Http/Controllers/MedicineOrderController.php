<?php

namespace App\Http\Controllers;

use App\Models\MedicineOrder;
use App\Models\PaymentMethod;
use App\Models\Pharmacy;
use App\Services\MedicineOrderService;
use App\Services\RewardPointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Checkout (turning the session cart into a real order) and the
 * patient's own order history. The actual stock-safe order placement
 * logic lives in MedicineOrderService — this controller just gathers
 * form input and calls it.
 */
class MedicineOrderController extends Controller
{
    public function __construct(private MedicineOrderService $orders, private RewardPointService $rewardPoints)
    {
    }

    /** Shows the checkout form: cart summary, delivery address, payment method (GET /patient/checkout). */
    public function checkout()
    {
        $cart = session('cart', ['pharmacy_id' => null, 'items' => []]);

        if (!$cart['pharmacy_id'] || empty($cart['items'])) {
            return redirect()->route('patient.cart')->withErrors(['cart' => 'Your cart is empty.']);
        }

        $pharmacy = Pharmacy::findOrFail($cart['pharmacy_id']);
        $preview = $this->orders->previewCart($pharmacy, $cart['items']);
        $patient = Auth::user()->patient;

        // Only Cash on Delivery is offered for real right now — bKash's
        // sandbox credentials in .env are still placeholders (see
        // README's "Turning on real bKash payments" section), so there's
        // no working payment gateway to redirect to yet.
        $paymentMethods = PaymentMethod::where('method_name', 'Cash')->get();

        return view('patient.checkout', [
            'pharmacy' => $pharmacy,
            'lines' => $preview['lines'],
            'subtotal' => $preview['subtotal'],
            'paymentMethods' => $paymentMethods,
            'defaultAddress' => $patient->address,
            'pointsBalance' => $patient->reward_points_balance,
            'redemptionOptions' => $this->rewardPoints->redemptionOptions($patient),
        ]);
    }

    /** Places the order (POST /patient/checkout). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'delivery_address' => ['required', 'string', 'max:255'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,payment_method_id'],
            // The dropdown only ever offers multiples of 10 up to this
            // patient's actual balance, but that's just the UI — the real
            // enforcement is previewRedemption()'s re-clamp inside
            // MedicineOrderService::placeOrder(), same as every other
            // "don't trust what the browser submitted" check in this app.
            'points_to_redeem' => ['nullable', 'integer', 'min:0'],
        ]);

        $cart = session('cart', ['pharmacy_id' => null, 'items' => []]);
        if (!$cart['pharmacy_id'] || empty($cart['items'])) {
            return redirect()->route('patient.cart')->withErrors(['cart' => 'Your cart is empty.']);
        }

        $pharmacy = Pharmacy::findOrFail($cart['pharmacy_id']);
        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);
        $patient = Auth::user()->patient;

        $result = $this->orders->placeOrder($patient, $pharmacy, $cart['items'], $data['delivery_address'], $paymentMethod, (int) ($data['points_to_redeem'] ?? 0));

        if (!$result['ok']) {
            return back()->withErrors(['order' => $result['message']]);
        }

        session()->forget('cart');

        return redirect()
            ->route('patient.orders')
            ->with('success', "Order placed! Order #{$result['order']->order_id}.");
    }

    // Shared by myOrders() below: which statuses are still "in motion" vs
    // settled one way or another (same idea as
    // AppointmentController::PENDING_STATUSES).
    private const PENDING_STATUSES = ['placed', 'accepted', 'out_for_delivery'];

    /** Patient: view their own order history, split into Pending/Completed and sorted earliest-placed first (GET /patient/orders). */
    public function myOrders()
    {
        $patientId = Auth::user()->patient->patient_id;
        $with = ['pharmacy', 'items.medicine', 'deliveryAgent', 'payment.paymentMethod'];

        $pending = MedicineOrder::where('patient_id', $patientId)
            ->whereIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('created_at')->get();

        $completed = MedicineOrder::where('patient_id', $patientId)
            ->whereNotIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('created_at')->get();

        return view('patient.my-orders', compact('pending', 'completed'));
    }
}
