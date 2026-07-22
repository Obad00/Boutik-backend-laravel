<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('shop_id', 40)->unique('uq_settings_shop_id');
            $table->string('shop_name', 150);
            $table->string('address', 255);
            $table->string('phone', 30);
            $table->string('receipt_footer', 255)->nullable();
            $table->string('admin_pin_hash');

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
