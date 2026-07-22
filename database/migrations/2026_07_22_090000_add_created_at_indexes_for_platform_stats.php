<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Les index existants sur orders/shops sont tous composites avec
        // shop_id en tête (isolation par boutique). Les agrégations
        // cross-tenant du superadmin filtrent par date sur TOUTES les
        // boutiques, donc shop_id en tête n'aide pas — il faut un index
        // dédié sur created_at seul pour borner le scan aux fenêtres
        // 30j/12 mois plutôt que de lire toute la table.
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at', 'idx_orders_created_at');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->index('created_at', 'idx_shops_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_created_at');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropIndex('idx_shops_created_at');
        });
    }
};
