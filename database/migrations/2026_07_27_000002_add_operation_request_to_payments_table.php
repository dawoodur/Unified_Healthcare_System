<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a payment be for an operation request, alongside the existing two
 * targets (appointment, medicine order) — same "exactly one target" shape,
 * just widened from 2 to 3 options. See OperationRequestController::accept().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('operation_request_id')->nullable()->after('medicine_order_id');
        });

        // Same reasoning as the original migration: no ON DELETE action
        // here because MySQL 8 forbids a referential action on a column
        // that's also part of a CHECK constraint.
        DB::statement('ALTER TABLE payments ADD CONSTRAINT fk_payments_operation_request FOREIGN KEY (operation_request_id) REFERENCES operation_requests(operation_request_id)');

        DB::statement('ALTER TABLE payments DROP CHECK chk_payments_one_target');
        DB::statement(
            'ALTER TABLE payments ADD CONSTRAINT chk_payments_one_target CHECK (
                (appointment_id IS NOT NULL) + (medicine_order_id IS NOT NULL) + (operation_request_id IS NOT NULL) = 1
            )'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payments DROP CHECK chk_payments_one_target');
        DB::statement(
            'ALTER TABLE payments ADD CONSTRAINT chk_payments_one_target CHECK (
                (appointment_id IS NOT NULL AND medicine_order_id IS NULL) OR
                (appointment_id IS NULL AND medicine_order_id IS NOT NULL)
            )'
        );

        DB::statement('ALTER TABLE payments DROP FOREIGN KEY fk_payments_operation_request');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('operation_request_id');
        });
    }
};
