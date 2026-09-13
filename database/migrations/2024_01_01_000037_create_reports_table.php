<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->unsignedBigInteger('reporter_account_id');
            $table->string('subject', 190);
            $table->text('description');
            $table->enum('status', ['open', 'in_review', 'resolved', 'dismissed'])->default('open');
            $table->text('admin_response')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('resolved_at')->nullable();

            $table->foreign('reporter_account_id')->references('account_id')->on('accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
