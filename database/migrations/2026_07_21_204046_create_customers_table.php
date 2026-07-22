<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('shop_id', 40);
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->decimal('current_debt', 12, 2)->default(0);
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->index('shop_id', 'idx_customers_shop_id');
            $table->index(['shop_id', 'current_debt'], 'idx_customers_shop_debt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
