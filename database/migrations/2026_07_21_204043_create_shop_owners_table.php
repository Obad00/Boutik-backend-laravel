<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_owners', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('shop_id', 40)->index('idx_shop_owners_shop_id');
            $table->string('name', 120);
            $table->string('email', 190)->nullable()->unique('uq_shop_owners_email');
            $table->string('password_hash');
            $table->enum('role', ['owner'])->default('owner');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_owners');
    }
};
