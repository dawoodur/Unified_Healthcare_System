<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a patient rate a delivery man too, not just a doctor/hospital/
 * pharmacy — same idea as the other three: one row per (patient, target),
 * enforced by four separate unique indexes below. MySQL/InnoDB treats NULL
 * as never equal to another NULL in a unique index, so
 * unique(patient_id, delivery_agent_id) only actually blocks a duplicate
 * when delivery_agent_id is set — rows reviewing a doctor/hospital/pharmacy
 * (where delivery_agent_id is NULL) are completely unaffected by it. That's
 * exactly what's wanted: "one review per patient per doctor" and "one
 * review per patient per delivery agent" as independent rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_agent_id')->nullable()->after('pharmacy_id');
        });

        DB::statement('ALTER TABLE reviews ADD CONSTRAINT fk_reviews_delivery_agent FOREIGN KEY (delivery_agent_id) REFERENCES delivery_agents(delivery_agent_id)');

        DB::statement('ALTER TABLE reviews DROP CHECK chk_review_one_target');
        DB::statement(
            'ALTER TABLE reviews ADD CONSTRAINT chk_review_one_target CHECK (
                (doctor_id IS NOT NULL) + (hospital_id IS NOT NULL) + (pharmacy_id IS NOT NULL) + (delivery_agent_id IS NOT NULL) = 1
            )'
        );

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['patient_id', 'doctor_id'], 'uq_review_patient_doctor');
            $table->unique(['patient_id', 'hospital_id'], 'uq_review_patient_hospital');
            $table->unique(['patient_id', 'pharmacy_id'], 'uq_review_patient_pharmacy');
            $table->unique(['patient_id', 'delivery_agent_id'], 'uq_review_patient_delivery');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('uq_review_patient_doctor');
            $table->dropUnique('uq_review_patient_hospital');
            $table->dropUnique('uq_review_patient_pharmacy');
            $table->dropUnique('uq_review_patient_delivery');
        });

        DB::statement('ALTER TABLE reviews DROP CHECK chk_review_one_target');
        DB::statement(
            'ALTER TABLE reviews ADD CONSTRAINT chk_review_one_target CHECK (
                (doctor_id IS NOT NULL AND hospital_id IS NULL AND pharmacy_id IS NULL) OR
                (doctor_id IS NULL AND hospital_id IS NOT NULL AND pharmacy_id IS NULL) OR
                (doctor_id IS NULL AND hospital_id IS NULL AND pharmacy_id IS NOT NULL)
            )'
        );

        DB::statement('ALTER TABLE reviews DROP FOREIGN KEY fk_reviews_delivery_agent');

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('delivery_agent_id');
        });
    }
};
