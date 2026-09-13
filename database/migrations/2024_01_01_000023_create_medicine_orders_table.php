<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('pharmacy_id');
            $table->unsignedBigInteger('prescription_id')->nullable();
            $table->unsignedBigInteger('delivery_agent_id')->nullable();
            $table->string('delivery_address', 255);
            $table->enum('status', ['placed', 'accepted', 'out_for_delivery', 'delivered', 'cancelled'])->default('placed');
            $table->unsignedInteger('reward_points_used')->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('pharmacy_id')->references('pharmacy_id')->on('pharmacies')->onDelete('cascade');
            $table->foreign('prescription_id')->references('prescription_id')->on('prescriptions')->onDelete('set null');
            $table->foreign('delivery_agent_id')->references('delivery_agent_id')->on('delivery_agents')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_orders');
    }
};
