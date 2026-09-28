<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Models\MedicineOrder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * JSON twin of the delivery agent's screens — the unclaimed queue, this
 * agent's own runs, and their profile summary.
 *
 * Read-only by design. Claiming an order, emailing the patient a
 * confirmation code, confirming a delivery and posting a live position all
 * stay on the existing web routes (DeliveryOrderController /
 * DeliveryTrackingController), which already own those side effects — the
 * React pages post to them rather than duplicating that logic.
 */
class DeliveryController extends Controller
{
    public function translations()
    {
        return response()->json([
            'locale' => App::getLocale(),
            'dashboard' => __('dashboard.delivery'),
        ]);
    }

    public function dashboard()
    {
        $agent = Auth::user()->deliveryAgent;
        $agentId = $agent->delivery_agent_id;

        $available = MedicineOrder::where('status', 'accepted')->whereNull('delivery_agent_id')->count();
        $active = MedicineOrder::where('delivery_agent_id', $agentId)->where('status', 'out_for_delivery')->count();
        $delivered = MedicineOrder::where('delivery_agent_id', $agentId)->where('status', 'delivered')->count();
        $earnings = MedicineOrder::where('delivery_agent_id', $agentId)->where('status', 'delivered')->sum('total_amount');

        return response()->json([
            'agent' => [
                'full_name' => $agent->full_name,
                'age' => $agent->age,
                'gender' => $agent->gender,
                'gender_label' => __('dashboard.' . $agent->gender),
                'blood_group' => $agent->blood_group,
                'uid_tag' => Auth::user()->uidTag(),
            ],
            'stats' => [
                'available' => (int) $available,
                'active' => (int) $active,
                'delivered' => (int) $delivered,
                'delivered_value' => (float) $earnings,
            ],
        ]);
    }

    /** Unclaimed orders any agent can take (GET /api/delivery/available). */
    public function available()
    {
        $orders = MedicineOrder::where('status', 'accepted')
            ->whereNull('delivery_agent_id')
            ->with(['pharmacy', 'patient'])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'orders' => $orders->map(fn (MedicineOrder $o) => $this->shape($o)),
        ]);
    }

    /** This agent's own runs, split into on-the-road and finished. */
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

        return response()->json([
            'pending' => $pending->map(fn (MedicineOrder $o) => $this->shape($o)),
            'completed' => $completed->map(fn (MedicineOrder $o) => $this->shape($o)),
        ]);
    }

    public function reviews()
    {
        $reviews = Auth::user()->deliveryAgent->reviews()
            ->orderByDesc('review_id')
            ->get(['rating', 'comment', 'created_at']);

        return response()->json([
            'reviews' => $reviews->map(fn ($review) => [
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'date_label' => $review->created_at?->format('M j, Y'),
            ]),
            'average' => $reviews->isEmpty() ? null : round($reviews->avg('rating'), 1),
            'count' => $reviews->count(),
            'breakdown' => collect(range(5, 1))
                ->map(fn ($star) => ['star' => $star, 'count' => $reviews->where('rating', $star)->count()])
                ->values(),
        ]);
    }

    private function shape(MedicineOrder $order): array
    {
        return [
            'order_id' => (int) $order->order_id,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'patient_name' => $order->patient?->full_name,
            'pharmacy_name' => $order->pharmacy?->pharmacy_name,
            'pharmacy_address' => $order->pharmacy?->address,
            'delivery_address' => $order->delivery_address,
            'total_amount' => $order->total_amount,
            'placed_label' => $order->created_at?->format('M j, Y g:i A'),
            // The live-sharing panel needs both ends of the route; the
            // patient's pin can be missing on older orders, which the React
            // card treats as "sharing unavailable for this one".
            'origin_lat' => $order->pharmacy?->latitude,
            'origin_lng' => $order->pharmacy?->longitude,
            'dest_lat' => $order->destination_latitude,
            'dest_lng' => $order->destination_longitude,
        ];
    }
}
