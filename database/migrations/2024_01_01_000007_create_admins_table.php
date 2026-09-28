<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id('admin_id');
            $table->unsignedBigInteger('account_id')->unique();
            $table->string('full_name', 150);

            $table->foreign('account_id')->references('account_id')->on('accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
