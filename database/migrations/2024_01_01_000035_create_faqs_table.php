<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id('faq_id');
            $table->string('question', 255);
            $table->text('answer');
            $table->string('category', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
