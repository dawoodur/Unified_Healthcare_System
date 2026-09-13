<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs the "possible conditions" section of the symptom checker — the
 * same keyword-matching idea as symptom_specialty_map/faq_keywords, just
 * pointed at a disease name + general advice instead of a specialty or
 * FAQ answer. This is explicitly NOT a diagnosis tool: see the disclaimer
 * rendered above every result in patient/symptom-checker.blade.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diseases', function (Blueprint $table) {
            $table->id('disease_id');
            $table->string('name', 150);
            $table->text('advice')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('disease_keywords', function (Blueprint $table) {
            $table->unsignedBigInteger('disease_id');
            $table->string('keyword', 100);

            $table->primary(['disease_id', 'keyword']);
            $table->foreign('disease_id')->references('disease_id')->on('diseases')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_keywords');
        Schema::dropIfExists('diseases');
    }
};
