<?php

namespace App\Http\Controllers;

use App\Models\MedicineOrder;
use App\Services\InboxService;
use App\Services\OtpService;
use App\Services\RewardPointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * A delivery agent's side of the order lifecycle: browse orders a
 * pharmacy has accepted but no agent has picked up yet, accept one,
 * and confirm the actual handoff with an email OTP sent to the patient
 * (purpose 'delivery_confirmation' — see database/migrations/..._create_otp_verifications_table.php),
 * the same OtpService used for login/registration, just a different purpose.
 */
class DeliveryOrderController extends Controller
{
    public function __construct(private OtpService $otp, private RewardPointService $rewardPoints, private InboxService $inbox)
    {
    }

    /** Orders ready for pickup that no agent has claimed yet (GET /delivery/available). */
    public function available()
    {
        $orders = MedicineOrder::where('status', 'accepted')
            ->whereNull('delivery_agent_id')
            ->with(['pharmacy', 'patient'])
            ->orderBy('created_at')
            ->get();

        return view('delivery.available', compact('orders'));
    }

    /** Claims an order for this agent (POST /delivery/orders/{order}/accept). */
    public function accept(MedicineOrder $order)
    {
        if ($order->status !== 'accepted' || $order->delivery_agent_id !== null) {
            return back()->withErrors(['status' => 'This order is no longer available.']);
        }

        $order->update([
            'status' => 'out_for_delivery',
            'delivery_agent_id' => Auth::user()->deliveryAgent->delivery_agent_id,
        ]);

        $this->inbox->ensureConversation($order->patient->account, Auth::user());

        // The agent lands on My deliveries with this run's route already
        // moving: spa-shell.blade.php passes the id to the React card, which
        // auto-starts the demo route in delivery-share-location.js. Flash
        // data, so a later reload of that page does not start it again.
        return redirect()->route('delivery.my-deliveries')
            ->with('success', 'Delivery accepted.')
            ->with('simulate_order', $order->order_id);
    }

    /** This agent's own deliveries, split into Pending/Completed and sorted earliest-accepted first (GET /delivery/my-deliveries). */
    public function myDeliveries()
    {
        $agentId = Auth::user()->deliveryAgent->delivery_agent_id;
        $with = ['pharmacy', 'patient'];

        $pending = MedicineOrder::where('delivery_agent_id', $agentId)
            ->where('status', 'out_for_delivery')
            ->with($with)->orderBy('created_at')->get();

        $completed = MedicineOrder::where('delivery_agent_id', $agentId)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->with($with)->orderBy('created_at')->get();

        return view('delivery.my-deliveries', compact('pending', 'completed'));
    }

    /** Emails a confirmation code to the patient, to be read aloud at handoff (POST /delivery/orders/{order}/request-otp). */
    public function requestOtp(MedicineOrder $order)
    {
        $this->authorizeAgent($order);

        if ($order->status !== 'out_for_delivery') {
            return back()->withErrors(['status' => 'This order is not out for delivery.']);
        }

        $result = $this->otp->issue($order->patient->account, 'delivery_confirmation', $order->order_id);

        if (!$result['sent']) {
            return back()->withErrors(['otp' => 'Could not email the code — the patient can check with support, or try again.']);
        }

        return back()->with('success', 'A confirmation code was emailed to the patient. Ask them for it to complete the delivery.');
    }

    /** Confirms delivery once the agent enters the code the patient read out (POST /delivery/orders/{order}/confirm). */
    public function confirm(Request $request, MedicineOrder $order)
    {
        $this->authorizeAgent($order);

        if ($order->status !== 'out_for_delivery') {
            return back()->withErrors(['status' => 'This order is not out for delivery.']);
        }

        $data = $request->validate(['otp_code' => ['required', 'string', 'size:6']]);

        $result = $this->otp->verify($order->patient->account, 'delivery_confirmation', $data['otp_code']);

        if (!$result['ok']) {
            return back()->withErrors(['otp_code' => $result['message']]);
        }

        $order->update(['status' => 'delivered']);
        // Cash on Delivery — the payment is only actually "completed" once
        // the delivery (and cash handoff) genuinely happened.
        $order->payment?->update(['status' => 'completed', 'paid_at' => now()]);

        // A medicine purchase earns points only after the COD handoff is
        // genuinely completed, not merely when an order is placed.
        $this->rewardPoints->award($order->patient, 'medicine_purchase', $order->order_id);

        return back()->with('success', 'Delivery confirmed.');
    }

    private function authorizeAgent(MedicineOrder $order): void
    {
        if ($order->delivery_agent_id !== Auth::user()->deliveryAgent->delivery_agent_id) {
            abort(403);
        }
    }
}
