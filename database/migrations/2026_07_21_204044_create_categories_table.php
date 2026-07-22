<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('shop_id', 40)->index('idx_categories_shop_id');
            $table->string('name', 100);

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
