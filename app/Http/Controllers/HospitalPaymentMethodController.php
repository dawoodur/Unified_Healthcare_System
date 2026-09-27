<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a hospital pick which payment methods it accepts (bKash, Cash,
 * Card, Bank Transfer — the same shared list medicine orders use, see
 * PaymentMethod.php) and note its own account details for each one, e.g.
 * a bKash merchant number. Purely informational for now — patients don't
 * pay through the app yet for appointments/facility bookings, so this is
 * just "what to bring/expect," the same way a real hospital's front desk
 * would tell you.
 */
class HospitalPaymentMethodController extends Controller
{
    /** Shows every payment method + whether this hospital accepts it (GET /hospital/payment-methods). */
    public function index()
    {
        $hospital = Auth::user()->hospital;

        $methods = PaymentMethod::orderBy('payment_method_id')->get();
        $accepted = $hospital->paymentMethods()->get()->keyBy('payment_method_id');

        return view('hospital.payment-methods', compact('methods', 'accepted'));
    }

    /**
     * Saves which methods are accepted, and their account details
     * (POST /hospital/payment-methods). One form submit replaces the
     * whole set — sync() adds/updates the checked ones and removes
     * anything unchecked, so the hospital never has to "remove" one
     * separately.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'accepted' => ['nullable', 'array'],
            'accepted.*' => ['integer', 'exists:payment_methods,payment_method_id'],
            'account_details' => ['nullable', 'array'],
            'account_details.*' => ['nullable', 'string', 'max:190'],
        ]);

        $acceptedIds = $data['accepted'] ?? [];
        $accountDetails = $data['account_details'] ?? [];

        $sync = [];
        foreach ($acceptedIds as $paymentMethodId) {
            $sync[$paymentMethodId] = ['account_details' => $accountDetails[$paymentMethodId] ?? null];
        }

        Auth::user()->hospital->paymentMethods()->sync($sync);

        return back()->with('success', 'Payment methods saved.');
    }
}
