<?php

namespace App\Http\Controllers\Api\Hospital;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** JSON twin of HospitalPaymentMethodController. */
class PaymentMethodController extends Controller
{
    public function index()
    {
        $hospital = Auth::user()->hospital;

        $accepted = $hospital->paymentMethods()->get()->keyBy('payment_method_id');

        return response()->json([
            'methods' => PaymentMethod::orderBy('payment_method_id')->get()->map(fn (PaymentMethod $m) => [
                'payment_method_id' => (int) $m->payment_method_id,
                'method_name' => $m->method_name,
                'accepted' => $accepted->has($m->payment_method_id),
                'account_details' => $accepted->get($m->payment_method_id)?->pivot?->account_details,
            ]),
        ]);
    }

    /**
     * One save replaces the whole set — sync() adds/updates the accepted
     * ones and removes anything left out, so a hospital never has to
     * "remove" a method separately.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'accepted' => ['nullable', 'array'],
            'accepted.*' => ['integer', 'exists:payment_methods,payment_method_id'],
            'account_details' => ['nullable', 'array'],
            'account_details.*' => ['nullable', 'string', 'max:190'],
        ]);

        $accountDetails = $data['account_details'] ?? [];

        $sync = [];
        foreach ($data['accepted'] ?? [] as $paymentMethodId) {
            $sync[$paymentMethodId] = ['account_details' => $accountDetails[$paymentMethodId] ?? null];
        }

        Auth::user()->hospital->paymentMethods()->sync($sync);

        return response()->json(['ok' => true, 'message' => 'Payment methods saved.']);
    }
}
