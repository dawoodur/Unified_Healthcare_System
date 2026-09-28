<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves "what this medicine is for" from individual catalog rows onto the
 * GENERIC, which is where the knowledge actually belongs: Napa, Ace and Fast
 * are all paracetamol. Once a generic is described, every brand of it — including
 * brands a pharmacy adds tomorrow — can be answered by the health chat with
 * no extra work.
 *
 * source tells you where an entry came from:
 *   curated        — written and reviewed by a human; trusted
 *   class_inferred — guessed from the drug-name stem by DrugClassGuesser
 *                    (e.g. "-floxacin" is an antibiotic). Safe, class-level
 *                    wording only, and always flagged needs_review so an
 *                    admin can confirm or correct it.
 *   unknown        — the name matched no known class; the chat says it does
 *                    not know rather than guessing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_generic_info', function (Blueprint $table) {
            $table->id('generic_info_id');
            $table->string('generic_name', 120)->unique();
            $table->text('uses_en')->nullable();
            $table->text('uses_bn')->nullable();
            $table->json('cautions_en')->nullable();
            $table->json('cautions_bn')->nullable();
            $table->boolean('is_prescription_only')->default(false);
            $table->enum('source', ['curated', 'class_inferred', 'unknown'])->default('unknown');
            $table->string('drug_class', 60)->nullable();
            $table->boolean('needs_review')->default(true);
            $table->timestamps();
        });

        // Carry the already-written entries across, keyed by generic.
        $seen = [];
        foreach (DB::table('medicine_master')->whereNotNull('uses_en')->get() as $row) {
            $key = mb_strtolower(trim($row->generic_name));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            DB::table('medicine_generic_info')->insert([
                'generic_name' => $key,
                'uses_en' => $row->uses_en,
                'uses_bn' => $row->uses_bn,
                'cautions_en' => $row->cautions_en,
                'cautions_bn' => $row->cautions_bn,
                'is_prescription_only' => $row->is_prescription_only,
                'source' => 'curated',
                'needs_review' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // One source of truth from here on: the brand row keeps only its own
        // Bangla spellings (aliases), the knowledge lives on the generic.
        Schema::table('medicine_master', function (Blueprint $table) {
            $table->dropColumn(['uses_en', 'uses_bn', 'cautions_en', 'cautions_bn', 'is_prescription_only']);
        });
    }

    public function down(): void
    {
        Schema::table('medicine_master', function (Blueprint $table) {
            $table->text('uses_en')->nullable();
            $table->text('uses_bn')->nullable();
            $table->json('cautions_en')->nullable();
            $table->json('cautions_bn')->nullable();
            $table->boolean('is_prescription_only')->default(false);
        });

        foreach (DB::table('medicine_generic_info')->get() as $info) {
            DB::table('medicine_master')
                ->whereRaw('LOWER(generic_name) = ?', [$info->generic_name])
                ->update([
                    'uses_en' => $info->uses_en,
                    'uses_bn' => $info->uses_bn,
                    'cautions_en' => $info->cautions_en,
                    'cautions_bn' => $info->cautions_bn,
                    'is_prescription_only' => $info->is_prescription_only,
                ]);
        }

        Schema::dropIfExists('medicine_generic_info');
    }
};
