<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            // tokenable_id must be string(40) to match our VARCHAR(40) model PKs
            // (ShopOwner / Superadmin) — the default morphs() uses an unsignedBigInteger,
            // which is incompatible and would silently break token->tokenable resolution.
            $table->string('tokenable_type');
            $table->string('tokenable_id', 40);
            $table->index(['tokenable_type', 'tokenable_id']);
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
