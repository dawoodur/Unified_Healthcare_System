<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id('otp_id');
            $table->unsignedBigInteger('account_id');
            $table->enum('purpose', ['registration_verify', 'login', 'record_access', 'delivery_confirmation']);
            $table->unsignedBigInteger('reference_id')->nullable()
                ->comment('e.g. record_access_grants.grant_id or medicine_orders.order_id');
            $table->char('otp_code', 6);
            $table->dateTime('expires_at');
            $table->boolean('is_used')->default(false);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('account_id')->references('account_id')->on('accounts')->onDelete('cascade');
            $table->index(['account_id', 'purpose', 'is_used'], 'idx_otp_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
