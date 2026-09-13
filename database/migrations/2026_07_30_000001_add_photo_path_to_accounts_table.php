<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One profile photo per account, regardless of role — lives here (not on
 * patients/doctors/etc.) since it's the same concept for every role and
 * Account is the one table every role already shares. Stored on the
 * PUBLIC disk (storage/app/public, symlinked to public/storage — see
 * ProfileController) because, unlike medical record files, a profile
 * photo is meant to be visible to other people (e.g. a patient seeing a
 * doctor's photo on their profile), not access-controlled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
