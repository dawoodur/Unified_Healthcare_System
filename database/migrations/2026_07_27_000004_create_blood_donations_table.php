<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = one patient self-reporting a blood donation — the permanent
 * history that patients.last_donated_at is just a cached MAX() of. Not
 * hospital-verified (this app has no in-person check-in step for it), the
 * same trust level as a patient self-reporting an allergy elsewhere in
 * this app — hospital_id is optional purely as a "where" note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_donations', function (Blueprint $table) {
            $table->id('donation_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->date('donated_at');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_donations');
    }
};
