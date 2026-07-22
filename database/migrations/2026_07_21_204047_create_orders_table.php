<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('shop_id', 40);
            $table->string('customer_id', 40)->nullable();
            $table->decimal('total', 12, 2);
            $table->enum('payment_mode', ['cash', 'credit']);
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->index('shop_id', 'idx_orders_shop_id');
            $table->index(['shop_id', 'created_at'], 'idx_orders_shop_created');
            $table->index(['shop_id', 'payment_mode'], 'idx_orders_shop_payment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
