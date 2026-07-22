<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('superadmins', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('name', 120);
            $table->string('email', 190)->unique('uq_superadmins_email');
            $table->string('password_hash');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('superadmins');
    }
};
