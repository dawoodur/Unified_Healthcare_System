<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_keywords', function (Blueprint $table) {
            $table->unsignedBigInteger('faq_id');
            $table->string('keyword', 100);
            $table->primary(['faq_id', 'keyword']);

            $table->foreign('faq_id')->references('faq_id')->on('faqs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_keywords');
    }
};
