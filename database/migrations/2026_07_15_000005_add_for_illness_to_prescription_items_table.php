<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a doctor say WHICH illness/condition each individual medicine in a
 * prescription is for (e.g. "Paracetamol" -> for_illness "Fever",
 * "Amoxicillin" -> for_illness "Throat infection") — separate from the
 * prescription's overall diagnosis_notes, since one visit can involve
 * several medicines for several different things.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->string('for_illness', 150)->nullable()->after('medicine_master_id');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropColumn('for_illness');
        });
    }
};
