<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_codes', function (Blueprint $table) {
            $table->string('code', 20)->primary();
            $table->string('shop_id', 40)->index('idx_shop_codes_shop_id');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_codes');
    }
};
