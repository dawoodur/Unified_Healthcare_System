<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a delivery agent was, moment by moment, while carrying one order.
 *
 * Append-only rather than a single "current position" column per order: the
 * patient's map draws the recent trail (so you can see which way the agent is
 * moving, not just a dot), and keeping each reading's own recorded_at is what
 * lets the page say "updated 4 seconds ago" and grey out a stale marker
 * instead of showing an old position as if it were live.
 *
 * Rows are only ever written while the order is out_for_delivery — see
 * App\Services\DeliveryTrackingService::isTrackable() — and die with the order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_locations', function (Blueprint $table) {
            $table->id('location_id');
            $table->unsignedBigInteger('order_id');

            // 7 decimal places is ~1cm of precision — far more than a phone
            // GPS gives, but decimal (not float) so repeated reads/writes
            // never drift the way binary floating point does.
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // What the browser reported as its margin of error, in metres.
            // Nullable because a simulated point has no real accuracy.
            $table->unsignedSmallInteger('accuracy_m')->nullable();

            // 'simulated' points are clearly labelled so a demo can never be
            // mistaken for a real GPS trace, in the DB or on the page.
            $table->enum('source', ['gps', 'simulated'])->default('gps');

            $table->timestamp('recorded_at');

            $table->foreign('order_id')->references('order_id')->on('medicine_orders')->onDelete('cascade');

            // Every read is "latest/recent points for this one order".
            $table->index(['order_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_locations');
    }
};
