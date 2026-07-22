<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('shop_id', 40);
            $table->string('order_id', 40)->nullable();
            $table->enum('type', ['in', 'out']);
            $table->decimal('amount', 12, 2);
            $table->string('reason', 255);
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->index('shop_id', 'idx_cash_movements_shop_id');
            $table->index(['shop_id', 'created_at'], 'idx_cash_movements_shop_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
