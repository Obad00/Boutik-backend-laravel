<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('order_id', 40);
            $table->string('product_id', 40);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->index('order_id', 'idx_order_items_order_id');
            $table->index('product_id', 'idx_order_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
