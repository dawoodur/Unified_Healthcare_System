<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A donation is no longer just self-reported: the patient logs it, the
 * hospital confirms it actually happened, and only then does it count.
 *
 * Confirmation is the moment everything hangs off — the 3-day countdown
 * before the patient can donate again starts at confirmed_at, and the
 * reward points are credited then too, so nobody can earn points or block
 * their own eligibility by logging a donation that never happened.
 *
 * Existing rows are historic demo data, so they are marked confirmed rather
 * than dropped into the hospitals' review queues.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blood_donations', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending')->after('donated_at');
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->string('reject_reason', 255)->nullable()->after('confirmed_at');
        });

        // confirmed_at drives the cooldown, so it has to be the date the blood
        // was actually given, not created_at (when the demo row happened to be
        // seeded) — otherwise a donation from months ago looks like it was
        // confirmed the day the seeder ran and puts that donor in a cooldown
        // they should have finished long ago.
        DB::table('blood_donations')->update([
            'status' => 'confirmed',
            'confirmed_at' => DB::raw('donated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('blood_donations', function (Blueprint $table) {
            $table->dropColumn(['status', 'confirmed_at', 'reject_reason']);
        });
    }
};
