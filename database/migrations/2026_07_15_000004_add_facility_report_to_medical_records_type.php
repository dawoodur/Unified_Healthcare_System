<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds 'facility_report' to medical_records.record_type — for the result
 * of a completed hospital facility (an MRI, a CT scan, a lab test, a
 * surgery outcome) that the hospital attaches when marking a booking
 * completed. Same idea as a prescription auto-becoming a medical record;
 * see HospitalFacilityController::markCompleted().
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE medical_records MODIFY record_type ENUM('prescription', 'lab_result', 'diagnosis_note', 'uploaded_document', 'facility_report') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE medical_records MODIFY record_type ENUM('prescription', 'lab_result', 'diagnosis_note', 'uploaded_document') NOT NULL");
    }
};
