<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drafts of medicine information fetched from a public drug API, waiting for
 * an admin to approve or reject them.
 *
 * Nothing here is ever shown to a patient. The health chat only reads
 * medicine_generic_info; a draft reaches patients only once an admin
 * approves it, which copies the (possibly edited) text across. That is
 * deliberate: the public sources available without an API key carry US drug
 * labels — legal prose written for a different market — so a human has to
 * decide whether it is accurate and understandable here before it is used.
 *
 * Fetching happens offline via `php artisan medicines:fetch-info`, never
 * during a patient's chat, so the assistant keeps working without internet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_info_drafts', function (Blueprint $table) {
            $table->id('draft_id');
            $table->string('generic_name', 120)->index();
            $table->string('source', 30)->default('openfda');
            $table->string('source_ref', 190)->nullable();
            $table->string('queried_as', 120)->nullable();
            $table->text('uses_en')->nullable();
            $table->json('cautions_en')->nullable();
            $table->boolean('suggested_prescription_only')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('accounts', 'account_id')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_info_drafts');
    }
};
