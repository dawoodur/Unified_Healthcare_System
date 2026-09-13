<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id('record_id');
            $table->unsignedBigInteger('patient_id');
            $table->enum('record_type', ['prescription', 'lab_result', 'diagnosis_note', 'uploaded_document']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('created_by_account_id');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('created_by_account_id')->references('account_id')->on('accounts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
