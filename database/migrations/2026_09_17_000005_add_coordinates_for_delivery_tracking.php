<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The two fixed points a delivery runs between, so the tracking map can show
 * more than a lone dot: where the order was collected (the pharmacy) and where
 * it is going (the patient's door).
 *
 * Addresses in this app are free text and there is no geocoding service wired
 * up, so neither can be derived from what we already store:
 *   - pharmacies get a stored coordinate, seeded below for existing rows, used
 *     as the starting point of a delivery and of the demo simulator;
 *   - an order's destination is nullable and filled in by the patient on the
 *     tracking page ("use my current location"), because only they can say
 *     where their door actually is. Everything still works without it — the
 *     map just shows the agent without a distance/ETA readout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::table('medicine_orders', function (Blueprint $table) {
            $table->decimal('destination_latitude', 10, 7)->nullable()->after('delivery_address');
            $table->decimal('destination_longitude', 10, 7)->nullable()->after('destination_latitude');
        });

        // Existing pharmacies are demo rows with text addresses only. Spread
        // them deterministically around central Dhaka (by pharmacy_id, so the
        // same pharmacy always lands on the same spot and re-running this
        // cannot shuffle the map) — close enough together that a delivery
        // between them is a believable few kilometres.
        $anchorLat = 23.7806;
        $anchorLng = 90.4074;

        foreach (DB::table('pharmacies')->select('pharmacy_id')->get() as $i => $pharmacy) {
            // ~0.01 degree is a little over a kilometre here; walking the
            // offsets in opposite directions keeps them from forming a line.
            $latOffset = (($i % 5) - 2) * 0.011;
            $lngOffset = ((int) floor($i / 5) % 5 - 2) * 0.013;

            DB::table('pharmacies')
                ->where('pharmacy_id', $pharmacy->pharmacy_id)
                ->update([
                    'latitude' => round($anchorLat + $latOffset, 7),
                    'longitude' => round($anchorLng + $lngOffset, 7),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('medicine_orders', function (Blueprint $table) {
            $table->dropColumn(['destination_latitude', 'destination_longitude']);
        });
    }
};
