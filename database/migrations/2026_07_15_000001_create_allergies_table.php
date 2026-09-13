<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = one known allergy for one patient (e.g. "Penicillin" ->
 * "rash"). Usually self-reported by the patient, but a doctor can add one
 * too if they discover it during a visit — recorded_by_account_id keeps
 * track of which. This is the "allergies" half of the EHR expansion; see
 * also create_vitals_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allergies', function (Blueprint $table) {
            $table->id('allergy_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('allergen', 150);
            $table->string('reaction', 255)->nullable();
            $table->unsignedBigInteger('recorded_by_account_id');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('recorded_by_account_id')->references('account_id')->on('accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allergies');
    }
};
