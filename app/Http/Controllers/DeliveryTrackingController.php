<?php

namespace App\Http\Controllers;

use App\Models\MedicineOrder;
use App\Services\DeliveryTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Live GPS tracking of a delivery, from both ends:
 *
 *   - the delivery agent's browser posts its position while carrying an order
 *     (store), either from the device's real GPS or from the demo simulator —
 *     tagged either way so the two can never be confused;
 *   - the patient watches it move (show + poll) and can pin their own door
 *     (setDestination) so the page can work out distance and an ETA.
 *
 * Both sides are refused outside the one stage where tracking makes sense
 * (out_for_delivery, agent assigned — see DeliveryTrackingService::isTrackable),
 * and each side may only ever touch its own order: an agent the order is
 * assigned to, or the patient who placed it.
 */
class DeliveryTrackingController extends Controller
{
    public function __construct(private DeliveryTrackingService $tracking)
    {
    }

    /** Records where the agent is now (POST /delivery/orders/{order}/location). */
    public function store(Request $request, MedicineOrder $order): JsonResponse
    {
        $this->authorizeAgent($order);

        if (! $this->tracking->isTrackable($order)) {
            // 409, not 422: the coordinates are fine, the order has simply
            // moved on — the agent's page uses this to stop its own watcher.
            return response()->json([
                'ok' => false,
                'tracking_ended' => true,
                'message' => 'This order is no longer out for delivery.',
            ], 409);
        }

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'source' => ['nullable', 'in:gps,simulated'],
        ]);

        $location = $this->tracking->record(
            $order,
            (float) $data['latitude'],
            (float) $data['longitude'],
            isset($data['accuracy_m']) ? (int) $data['accuracy_m'] : null,
            $data['source'] ?? 'gps'
        );

        return response()->json([
            'ok' => true,
            'recorded_at' => $location->recorded_at->toIso8601String(),
        ]);
    }

    /** The patient's tracking page (GET /patient/orders/{order}/track). */
    public function show(MedicineOrder $order)
    {
        $this->authorizePatient($order);

        $order->load(['pharmacy', 'deliveryAgent']);

        return view('patient.orders.track', [
            'order' => $order,
            'snapshot' => $this->tracking->snapshot($order),
        ]);
    }

    /** Polled by the page every few seconds (GET /patient/orders/{order}/track/poll). */
    public function poll(MedicineOrder $order): JsonResponse
    {
        $this->authorizePatient($order);

        $order->load(['pharmacy', 'deliveryAgent']);

        return response()->json($this->tracking->snapshot($order));
    }

    /**
     * Pins the patient's own door (POST /patient/orders/{order}/destination).
     *
     * Only the patient can do this: addresses here are free text and there is
     * no geocoder, so the delivery address string alone cannot be turned into
     * a coordinate — the person standing at the door is the only reliable
     * source for it.
     */
    public function setDestination(Request $request, MedicineOrder $order): JsonResponse
    {
        $this->authorizePatient($order);

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $order->update([
            'destination_latitude' => (float) $data['latitude'],
            'destination_longitude' => (float) $data['longitude'],
        ]);

        return response()->json([
            'ok' => true,
            'snapshot' => $this->tracking->snapshot($order->fresh(['pharmacy', 'deliveryAgent'])),
        ]);
    }

    /** The signed-in agent must be the one this order was assigned to. */
    private function authorizeAgent(MedicineOrder $order): void
    {
        if ($order->delivery_agent_id !== Auth::user()->deliveryAgent?->delivery_agent_id) {
            abort(403);
        }
    }

    /** A patient may only ever track an order they placed themselves. */
    private function authorizePatient(MedicineOrder $order): void
    {
        if ($order->patient_id !== Auth::user()->patient?->patient_id) {
            abort(403);
        }
    }
}
