<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('account_id')->comment('payer');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('medicine_order_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->unsignedBigInteger('payment_method_id');
            $table->string('bkash_payment_id', 100)->nullable()->unique();
            $table->string('bkash_trx_id', 100)->nullable();
            $table->enum('status', ['initiated', 'pending', 'completed', 'failed', 'refunded'])->default('initiated');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('account_id')->references('account_id')->on('accounts')->onDelete('cascade');
            // No ON DELETE SET NULL here: MySQL 8 forbids a column from having both a referential
            // action and a CHECK constraint (chk_payments_one_target below) on the same column.
            // Financial records shouldn't have their appointment/order silently nulled out anyway.
            $table->foreign('appointment_id')->references('appointment_id')->on('appointments');
            $table->foreign('medicine_order_id')->references('order_id')->on('medicine_orders');
            $table->foreign('payment_method_id')->references('payment_method_id')->on('payment_methods');
        });

        DB::statement(
            'ALTER TABLE payments ADD CONSTRAINT chk_payments_one_target CHECK (
                (appointment_id IS NOT NULL AND medicine_order_id IS NULL) OR
                (appointment_id IS NULL AND medicine_order_id IS NOT NULL)
            )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
