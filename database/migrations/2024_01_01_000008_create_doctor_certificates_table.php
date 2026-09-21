<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_certificates', function (Blueprint $table) {
            $table->id('certificate_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('file_path', 255);
            $table->dateTime('uploaded_at')->useCurrent();
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by_admin_id')->nullable();
            $table->dateTime('reviewed_at')->nullable();

            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
            $table->foreign('reviewed_by_admin_id')->references('admin_id')->on('admins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_certificates');
    }
};
