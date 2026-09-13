<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_master', function (Blueprint $table) {
            $table->id('medicine_master_id');
            $table->string('generic_name', 150);
            $table->string('brand_name', 150)->nullable();
            $table->string('form', 60)->nullable()->comment('tablet, syrup, injection, ...');
            $table->string('strength', 60)->nullable();
            $table->index('generic_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_master');
    }
};
