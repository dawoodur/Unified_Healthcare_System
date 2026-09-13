<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id('review_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('pharmacy_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('comment', 500)->nullable();
            $table->dateTime('created_at')->useCurrent();

            // No SET NULL/CASCADE on doctor_id/hospital_id/pharmacy_id/appointment_id/order_id: MySQL 8
            // forbids a referential action on a column that's also part of a CHECK constraint
            // (chk_review_one_target below).
            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors');
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals');
            $table->foreign('pharmacy_id')->references('pharmacy_id')->on('pharmacies');
            $table->foreign('appointment_id')->references('appointment_id')->on('appointments');
            $table->foreign('order_id')->references('order_id')->on('medicine_orders');
        });

        DB::statement('ALTER TABLE reviews ADD CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)');
        DB::statement(
            'ALTER TABLE reviews ADD CONSTRAINT chk_review_one_target CHECK (
                (doctor_id IS NOT NULL AND hospital_id IS NULL AND pharmacy_id IS NULL) OR
                (doctor_id IS NULL AND hospital_id IS NOT NULL AND pharmacy_id IS NULL) OR
                (doctor_id IS NULL AND hospital_id IS NULL AND pharmacy_id IS NOT NULL)
            )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
