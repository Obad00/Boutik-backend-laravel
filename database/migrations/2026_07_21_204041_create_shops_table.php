<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('name', 150);
            $table->string('address', 255);
            $table->string('phone', 30);
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->string('created_by', 40)->nullable();

            $table->foreign('created_by')->references('id')->on('superadmins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
