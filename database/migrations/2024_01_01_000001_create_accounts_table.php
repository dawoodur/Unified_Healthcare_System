<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id('account_id');
            $table->enum('role', ['patient', 'doctor', 'hospital', 'pharmacy', 'delivery', 'admin']);
            $table->string('email', 190)->unique();
            $table->string('mobile', 20)->nullable()->unique();
            $table->string('password_hash', 255);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->dateTime('locked_until')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
