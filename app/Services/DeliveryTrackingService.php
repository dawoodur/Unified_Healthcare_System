<?php

namespace App\Services;

use App\Models\DeliveryLocation;
use App\Models\MedicineOrder;
use Illuminate\Support\Collection;

/**
 * Everything the delivery map needs, in one place: whether an order may be
 * tracked at all, recording a position, and turning two coordinates into the
 * "1.2 km away · about 6 min" line the patient actually reads.
 *
 * Deliberately the single authority on those rules so the agent endpoint, the
 * patient page and the polling endpoint cannot disagree about, say, whether a
 * delivered order is still live.
 */
class DeliveryTrackingService
{
    /**
     * A reading older than this is shown as stale rather than current — the
     * agent likely closed the tab, lost signal, or went out of a tunnel.
     * Comfortably longer than the 10s the browser sends on, so ordinary
     * jitter never trips it.
     */
    public const STALE_AFTER_SECONDS = 90;

    /**
     * Rough city-scooter pace through Dhaka traffic, used only for the ETA
     * estimate. Straight-line distance already understates the real route, so
     * a deliberately modest speed keeps the estimate from being over-optimistic.
     */
    public const AVERAGE_SPEED_KMH = 18.0;

    /** Earth's mean radius in kilometres (haversine). */
    private const EARTH_RADIUS_KM = 6371.0088;

    /**
     * Tracking is live for exactly one stage of the order's life: an agent has
     * claimed it and has not yet completed the handoff. Before that there is
     * nobody to track; after it, the agent's whereabouts are no longer the
     * patient's business.
     */
    public function isTrackable(MedicineOrder $order): bool
    {
        return $order->status === 'out_for_delivery' && $order->delivery_agent_id !== null;
    }

    /** Stores one position reading for an order. */
    public function record(
        MedicineOrder $order,
        float $latitude,
        float $longitude,
        ?int $accuracyMetres = null,
        string $source = 'gps'
    ): DeliveryLocation {
        return DeliveryLocation::create([
            'order_id' => $order->order_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_m' => $accuracyMetres,
            'source' => $source === 'simulated' ? 'simulated' : 'gps',
            'recorded_at' => now(),
        ]);
    }

    /** The most recent reading, or null if the agent has never shared one. */
    public function latest(MedicineOrder $order): ?DeliveryLocation
    {
        return DeliveryLocation::where('order_id', $order->order_id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('location_id')
            ->first();
    }

    /**
     * Recent readings oldest-first, for drawing the trail behind the marker.
     * Capped so a long delivery cannot grow the payload without bound.
     */
    public function trail(MedicineOrder $order, int $limit = 60): Collection
    {
        return DeliveryLocation::where('order_id', $order->order_id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('location_id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /** Great-circle distance between two points, in kilometres. */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Seconds to cover a straight-line distance at city pace. Floored at 30s
     * so an agent standing on the doorstep still shows a moving countdown
     * rather than a frozen zero.
     */
    public function etaSeconds(float $distanceKm): int
    {
        return max(30, (int) round(($distanceKm / self::AVERAGE_SPEED_KMH) * 3600));
    }

    /**
     * The same estimate in whole minutes, never less than 1 — "0 min away"
     * reads like a bug to someone still waiting at the door. Derived from
     * etaSeconds() so the two can never disagree on screen.
     */
    public function etaMinutes(float $distanceKm): int
    {
        return max(1, (int) ceil($this->etaSeconds($distanceKm) / 60));
    }

    /**
     * The whole tracking picture for one order, shaped for both the Blade page
     * and the polling endpoint so the two can never drift apart.
     */
    public function snapshot(MedicineOrder $order): array
    {
        $latest = $this->latest($order);
        $trackable = $this->isTrackable($order);

        $destination = $this->destination($order);
        $distanceKm = null;

        if ($latest && $destination) {
            $distanceKm = $this->distanceKm(
                $latest->latitude,
                $latest->longitude,
                $destination['latitude'],
                $destination['longitude']
            );
        }

        return [
            'trackable' => $trackable,
            'status' => $order->status,
            'agent_name' => $order->deliveryAgent?->full_name,
            'sharing' => $latest !== null
                && $latest->recorded_at->diffInSeconds(now()) <= self::STALE_AFTER_SECONDS,
            'position' => $latest ? [
                'latitude' => $latest->latitude,
                'longitude' => $latest->longitude,
                'accuracy_m' => $latest->accuracy_m,
                'simulated' => $latest->isSimulated(),
                'recorded_at' => $latest->recorded_at->toIso8601String(),
                'age_seconds' => $latest->recorded_at->diffInSeconds(now()),
            ] : null,
            'trail' => $trackable
                ? $this->trail($order)->map(fn (DeliveryLocation $p) => [
                    'latitude' => $p->latitude,
                    'longitude' => $p->longitude,
                ])->all()
                : [],
            'destination' => $destination,
            'origin' => $this->origin($order),
            'distance_km' => $distanceKm !== null ? round($distanceKm, 2) : null,
            'eta_minutes' => $distanceKm !== null ? $this->etaMinutes($distanceKm) : null,
            'eta_seconds' => $distanceKm !== null ? $this->etaSeconds($distanceKm) : null,
            // An absolute instant, not a duration: the page counts down to it
            // second by second between the 5s polls, and each poll re-bases it
            // on the server's own estimate, so the timer can drift neither
            // ahead of nor behind what the server actually believes.
            'arrival_at' => $distanceKm !== null
                ? now()->addSeconds($this->etaSeconds($distanceKm))->toIso8601String()
                : null,
        ];
    }

    /** The patient's door, if they have pinned it. */
    public function destination(MedicineOrder $order): ?array
    {
        if ($order->destination_latitude === null || $order->destination_longitude === null) {
            return null;
        }

        return [
            'latitude' => (float) $order->destination_latitude,
            'longitude' => (float) $order->destination_longitude,
            'label' => $order->delivery_address,
        ];
    }

    /** The pharmacy the order was collected from, if it has coordinates. */
    public function origin(MedicineOrder $order): ?array
    {
        $pharmacy = $order->pharmacy;

        if (! $pharmacy || $pharmacy->latitude === null || $pharmacy->longitude === null) {
            return null;
        }

        return [
            'latitude' => (float) $pharmacy->latitude,
            'longitude' => (float) $pharmacy->longitude,
            'label' => $pharmacy->pharmacy_name,
        ];
    }
}
