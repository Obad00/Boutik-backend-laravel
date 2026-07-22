<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('shop_id', 40);
            $table->string('product_id', 40);
            $table->enum('type', ['in', 'out']);
            $table->decimal('quantity', 12, 2);
            $table->string('reason', 255);
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index('shop_id', 'idx_stock_movements_shop_id');
            $table->index(['shop_id', 'created_at'], 'idx_stock_movements_shop_created');
            $table->index('product_id', 'idx_stock_movements_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
