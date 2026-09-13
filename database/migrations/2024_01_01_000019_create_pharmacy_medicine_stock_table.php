<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_medicine_stock', function (Blueprint $table) {
            $table->id('stock_id');
            $table->unsignedBigInteger('pharmacy_id');
            $table->unsignedBigInteger('medicine_master_id');
            $table->string('batch_no', 60);
            $table->date('expiry_date');
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity_available')->default(0);

            $table->unique(['pharmacy_id', 'medicine_master_id', 'batch_no'], 'uq_pharmacy_medicine_batch');
            $table->foreign('pharmacy_id')->references('pharmacy_id')->on('pharmacies')->onDelete('cascade');
            $table->foreign('medicine_master_id')->references('medicine_master_id')->on('medicine_master')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_medicine_stock');
    }
};
