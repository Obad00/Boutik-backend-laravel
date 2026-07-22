<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('shop_id', 40);
            $table->string('category_id', 40);
            $table->string('name', 150);
            $table->decimal('price_sell', 12, 2);
            $table->decimal('price_buy', 12, 2)->nullable();
            $table->enum('unit', ['piece', 'sachet', 'carton', 'kg', 'litre', 'boite'])->default('piece');
            $table->decimal('stock_quantity', 12, 2)->default(0);
            $table->decimal('stock_alert_threshold', 12, 2)->default(5);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->index('shop_id', 'idx_products_shop_id');
            $table->index(['shop_id', 'category_id'], 'idx_products_shop_category');
            $table->index(['shop_id', 'name'], 'idx_products_shop_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
