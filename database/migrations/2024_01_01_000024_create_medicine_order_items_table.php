<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_order_items', function (Blueprint $table) {
            $table->id('order_item_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('medicine_master_id');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_snapshot', 10, 2);

            $table->foreign('order_id')->references('order_id')->on('medicine_orders')->onDelete('cascade');
            $table->foreign('medicine_master_id')->references('medicine_master_id')->on('medicine_master');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_order_items');
    }
};
