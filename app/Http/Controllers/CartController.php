<?php

namespace App\Http\Controllers;

use App\Models\Pharmacy;
use App\Services\MedicineOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The patient's shopping cart, kept in the session (not a database
 * table) — it's throwaway data that only matters until checkout, the
 * same reasoning most simple e-commerce carts use. A cart can only ever
 * hold medicines from ONE pharmacy at a time, because medicine_orders
 * itself only supports one pharmacy_id per order — see add() below for
 * how that's enforced. Session shape: ['pharmacy_id' => ?int, 'items' => [medicine_master_id => quantity]].
 */
class CartController extends Controller
{
    public function __construct(private MedicineOrderService $orders)
    {
    }

    /** Shows the cart's contents and running total (GET /patient/cart). */
    public function show()
    {
        $cart = session('cart', ['pharmacy_id' => null, 'items' => []]);
        $pharmacy = $cart['pharmacy_id'] ? Pharmacy::find($cart['pharmacy_id']) : null;

        $preview = $pharmacy
            ? $this->orders->previewCart($pharmacy, $cart['items'])
            : ['lines' => collect(), 'subtotal' => 0];

        return view('patient.cart', [
            'pharmacy' => $pharmacy,
            'lines' => $preview['lines'],
            'subtotal' => $preview['subtotal'],
        ]);
    }

    /** Adds one medicine (from one pharmacy) to the cart (POST /patient/cart/add). */
    public function add(Request $request)
    {
        $data = $request->validate([
            'pharmacy_id' => ['required', 'integer', 'exists:pharmacies,pharmacy_id'],
            'medicine_master_id' => ['required', 'integer', 'exists:medicine_master,medicine_master_id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        // Laravel's 'integer' validation rule only checks the format —
        // the value coming back from validate() is still the raw string
        // the form submitted, so cast both explicitly before using them
        // in strict (===/in_array(..., true)) comparisons below.
        $pharmacyId = (int) $data['pharmacy_id'];
        $medicineMasterId = (int) $data['medicine_master_id'];

        // A patient can only order medicine a doctor has actually
        // prescribed them — see Patient::prescribedMedicineIds(). Checked
        // here (not just hidden in the UI) so this can't be bypassed by
        // posting straight to this route.
        $patient = Auth::user()->patient;
        if (!in_array($medicineMasterId, $patient->prescribedMedicineIds(), true)) {
            return back()->withErrors([
                'cart' => 'You can only order medicine that a doctor has prescribed you. See My Prescriptions.',
            ]);
        }

        $cart = session('cart', ['pharmacy_id' => null, 'items' => []]);

        if ($cart['pharmacy_id'] && (int) $cart['pharmacy_id'] !== $pharmacyId) {
            $currentPharmacy = Pharmacy::find($cart['pharmacy_id']);
            return back()->withErrors([
                'cart' => "Your cart already has items from {$currentPharmacy->pharmacy_name}. Clear your cart first to order from a different pharmacy.",
            ]);
        }

        $cart['pharmacy_id'] = $pharmacyId;
        $cart['items'][$medicineMasterId] = ($cart['items'][$medicineMasterId] ?? 0) + (int) $data['quantity'];
        session(['cart' => $cart]);

        return back()->with('success', 'Added to cart.');
    }

    /** Reduces one medicine quantity by one; removes the line when quantity reaches zero (POST /patient/cart/remove). */
    public function remove(Request $request)
    {
        $data = $request->validate(['medicine_master_id' => ['required', 'integer']]);

        $medicineMasterId = (int) $data['medicine_master_id'];
        $cart = session('cart', ['pharmacy_id' => null, 'items' => []]);

        if (isset($cart['items'][$medicineMasterId])) {
            $cart['items'][$medicineMasterId] = max(0, (int) $cart['items'][$medicineMasterId] - 1);

            if ($cart['items'][$medicineMasterId] === 0) {
                unset($cart['items'][$medicineMasterId]);
            }
        }

        if (empty($cart['items'])) {
            $cart['pharmacy_id'] = null; // nothing left — free up the cart to order from any pharmacy again
        }

        session(['cart' => $cart]);

        return back()->with('success', 'Cart quantity updated.');
    }

    /** Empties the cart entirely (POST /patient/cart/clear). */
    public function clear()
    {
        session()->forget('cart');

        return back()->with('success', 'Cart cleared.');
    }
}
