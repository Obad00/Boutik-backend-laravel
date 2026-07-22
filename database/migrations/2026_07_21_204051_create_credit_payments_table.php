<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_payments', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('shop_id', 40);
            $table->string('customer_id', 40);
            $table->decimal('amount', 12, 2);
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->index('shop_id', 'idx_credit_payments_shop_id');
            $table->index('customer_id', 'idx_credit_payments_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
    }
};
