<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a prescription come from a patient scanning their own paper document
 * (see PrescriptionOcrService/PrescriptionScanController) instead of a
 * doctor issuing one in-app. A scanned prescription has no real appointment
 * or platform doctor account behind it — the doctor/hospital named on the
 * document are recorded as plain text, and appointment_id/doctor_id have to
 * become nullable to allow that. No doctrine/dbal in this project, so the
 * MODIFY is raw SQL rather than Blueprint::change().
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE prescriptions MODIFY appointment_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE prescriptions MODIFY doctor_id BIGINT UNSIGNED NULL');

        Schema::table('prescriptions', function (Blueprint $table) {
            // 'verified' is the default so every existing (and future
            // doctor-issued) row needs no backfill — only a patient_scanned
            // row is ever created as 'pending_review'. See
            // Patient::unverifiedScannedMedicineIds(), which is what this
            // actually gates ordering on.
            $table->enum('source', ['doctor_issued', 'patient_scanned'])->default('doctor_issued')->after('patient_id');
            $table->enum('verification_status', ['verified', 'pending_review', 'rejected'])->default('verified')->after('source');
            $table->unsignedBigInteger('medical_record_id')->nullable()->after('verification_status');
            // The name OCR found on the document and matched against the
            // patient's account name — kept for an admin reviewer to see
            // what the match was actually based on.
            $table->string('scanned_patient_name', 255)->nullable()->after('medical_record_id');
            $table->string('external_doctor_name', 255)->nullable()->after('scanned_patient_name');
            $table->string('external_hospital_name', 255)->nullable()->after('external_doctor_name');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('external_hospital_name');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');

            $table->foreign('medical_record_id')->references('record_id')->on('medical_records')->onDelete('set null');
            $table->foreign('reviewed_by')->references('account_id')->on('accounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['medical_record_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'source', 'verification_status', 'medical_record_id',
                'scanned_patient_name', 'external_doctor_name', 'external_hospital_name',
                'reviewed_by', 'reviewed_at',
            ]);
        });

        DB::statement('ALTER TABLE prescriptions MODIFY doctor_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE prescriptions MODIFY appointment_id BIGINT UNSIGNED NOT NULL');
    }
};
