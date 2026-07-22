<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Seul endroit du SaaS où des agrégations cross-tenant sont légitimes — tout
 * ici scanne délibérément toutes les boutiques. Ne jamais réutiliser ces
 * requêtes dans un contexte scope par shop_id.
 */
class PlatformStatsService
{
    public function getStats(): array
    {
        return [
            'overview' => $this->overview(),
            'trends' => $this->trends(),
            'rankings' => $this->rankings(),
        ];
    }

    /**
     * La suite de tests tourne sur sqlite (voir phpunit.xml), la prod sur
     * MySQL (voir .env / mysql/schema.sql) — DATE_FORMAT/DATE() n'existent
     * pas côté sqlite, donc les expressions de troncature de date doivent
     * rester pilotées par le driver plutôt que codées en dur pour MySQL.
     */
    private function monthExpr(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function dateExpr(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "date({$column})"
            : "DATE({$column})";
    }

    private function overview(): array
    {
        $shopCounts = Shop::selectRaw('COUNT(*) as total, SUM(is_active) as active')->first();
        $agg = $this->fullScanAggregates();

        return [
            'shops_total' => (int) $shopCounts->total,
            'shops_active' => (int) $shopCounts->active,
            'shops_inactive' => (int) $shopCounts->total - (int) $shopCounts->active,
            'shops_new_this_week' => Shop::where('created_at', '>=', now()->subDays(7))->count(),
            'shops_new_this_month' => Shop::where('created_at', '>=', now()->subDays(30))->count(),
            'revenue_total' => $agg['revenue_total'],
            'revenue_cash' => $agg['revenue_cash'],
            'revenue_credit' => $agg['revenue_credit'],
            'sales_count' => $agg['sales_count'],
            'average_basket' => $agg['sales_count'] > 0
                ? round($agg['revenue_total'] / $agg['sales_count'], 2)
                : 0.0,
            'total_debt' => $agg['total_debt'],
            'stock_value_total' => $agg['stock_value_total'],
        ];
    }

    /**
     * Les 3 requêtes ci-dessous (CA/ventes, dette, stock) scannent chacune une
     * table entière "à vie", toutes boutiques confondues — aucun filtre de
     * date ne peut les borner, donc aucun index ne les rend moins coûteuses
     * qu'un scan complet. C'est le candidat naturel au cache court.
     *
     * Cache-ready via superadmin.stats_cache_enabled (désactivé par défaut :
     * on veut voir le coût réel avant de décider de l'activer).
     */
    private function fullScanAggregates(): array
    {
        $compute = function (): array {
            $revenue = Order::selectRaw("
                    COALESCE(SUM(total), 0) as revenue_total,
                    COALESCE(SUM(CASE WHEN payment_mode = 'cash' THEN total ELSE 0 END), 0) as revenue_cash,
                    COALESCE(SUM(CASE WHEN payment_mode = 'credit' THEN total ELSE 0 END), 0) as revenue_credit,
                    COUNT(*) as sales_count
                ")->first();

            // Exclut les produits sans price_buy renseigné (champ optionnel,
            // voir migration create_products_table). Sous-estimation
            // potentielle de la valeur de stock réelle si des boutiques
            // n'ont pas saisi leur prix d'achat — ce n'est pas un bug, on ne
            // peut pas valoriser un stock dont on ne connaît pas le coût.
            $stockValue = Product::whereNotNull('price_buy')
                ->selectRaw('COALESCE(SUM(stock_quantity * price_buy), 0) as value')
                ->value('value');

            return [
                'revenue_total' => (float) $revenue->revenue_total,
                'revenue_cash' => (float) $revenue->revenue_cash,
                'revenue_credit' => (float) $revenue->revenue_credit,
                'sales_count' => (int) $revenue->sales_count,
                'total_debt' => (float) Customer::sum('current_debt'),
                'stock_value_total' => (float) $stockValue,
            ];
        };

        if (! config('superadmin.stats_cache_enabled')) {
            return $compute();
        }

        return Cache::remember(
            'superadmin:stats:full-scan-aggregates',
            config('superadmin.stats_cache_ttl'),
            $compute
        );
    }

    private function trends(): array
    {
        $newShopsByMonth = Shop::where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw($this->monthExpr('created_at').' as month, COUNT(*) as count')
            ->groupBy('month')->orderBy('month')->get();

        $revenueByDay = Order::where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->selectRaw($this->dateExpr('created_at').' as date, SUM(total) as total')
            ->groupBy('date')->orderBy('date')->get();

        $revenueByMonth = Order::where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw($this->monthExpr('created_at').' as month, SUM(total) as total')
            ->groupBy('month')->orderBy('month')->get();

        $salesCountByDay = Order::where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->selectRaw($this->dateExpr('created_at').' as date, COUNT(*) as count')
            ->groupBy('date')->orderBy('date')->get();

        return [
            'new_shops_by_month' => $newShopsByMonth
                ->map(fn ($r) => ['month' => $r->month, 'count' => (int) $r->count])->all(),
            'revenue_by_day' => $revenueByDay
                ->map(fn ($r) => ['date' => $r->date, 'total' => (float) $r->total])->all(),
            'revenue_by_month' => $revenueByMonth
                ->map(fn ($r) => ['month' => $r->month, 'total' => (float) $r->total])->all(),
            'sales_count_by_day' => $salesCountByDay
                ->map(fn ($r) => ['date' => $r->date, 'count' => (int) $r->count])->all(),
        ];
    }

    private function rankings(): array
    {
        // Rankings limités aux boutiques actives (validé) : une boutique
        // désactivée n'a plus vocation à apparaître dans un palmarès.
        //
        // Une seule requête groupée (coûteuse : JOIN + GROUP BY sur tout
        // orders, ~2.3s mesurés à 300k commandes / 60 boutiques) au lieu de
        // deux — le tri par CA vs par nb de ventes se fait ensuite en PHP
        // sur les ~N lignes (une par boutique), sans repayer le scan.
        $shopTotals = Order::query()
            ->join('shops', 'shops.id', '=', 'orders.shop_id')
            ->where('shops.is_active', true)
            ->selectRaw('orders.shop_id, shops.name as shop_name, SUM(orders.total) as revenue, COUNT(*) as sales_count')
            ->groupBy('orders.shop_id', 'shops.name')
            ->get();

        $topByRevenue = $shopTotals->sortByDesc('revenue')->take(10)->values();
        $topBySalesCount = $shopTotals->sortByDesc('sales_count')->take(10)->values();

        // Bien servie par l'index composite existant idx_orders_shop_created
        // (shop_id, created_at) : MAX(created_at)/COUNT(*) par shop_id se
        // résout via un loose index scan, sans nouvel index dédié.
        $activity = Shop::where('is_active', true)
            ->leftJoinSub(
                DB::table('orders')
                    ->selectRaw('shop_id, MAX(created_at) as last_order_at, COUNT(*) as orders_count')
                    ->groupBy('shop_id'),
                'o',
                'o.shop_id',
                '=',
                'shops.id'
            )
            ->select('shops.id', 'shops.name', 'shops.created_at', 'o.last_order_at', 'o.orders_count')
            ->get();

        $cutoff = Carbon::now()->subDays(14);

        // Listes disjointes (validé) : une boutique jamais vendue n'apparaît
        // que dans shops_never_sold, jamais aussi dans shops_inactive_14d.
        $neverSold = $activity->filter(fn ($r) => $r->orders_count === null);
        $inactive14d = $activity->filter(
            fn ($r) => $r->orders_count !== null && Carbon::parse($r->last_order_at)->lt($cutoff)
        );

        return [
            'top_shops_by_revenue' => $topByRevenue->map(fn ($r) => [
                'shop_id' => $r->shop_id,
                'shop_name' => $r->shop_name,
                'revenue' => (float) $r->revenue,
                'sales_count' => (int) $r->sales_count,
            ])->all(),
            'top_shops_by_sales_count' => $topBySalesCount->map(fn ($r) => [
                'shop_id' => $r->shop_id,
                'shop_name' => $r->shop_name,
                'sales_count' => (int) $r->sales_count,
                'revenue' => (float) $r->revenue,
            ])->all(),
            'shops_never_sold' => $neverSold->map(fn ($r) => [
                'shop_id' => $r->id,
                'shop_name' => $r->name,
                'created_at' => $r->created_at,
            ])->values()->all(),
            'shops_inactive_14d' => $inactive14d->map(fn ($r) => [
                'shop_id' => $r->id,
                'shop_name' => $r->name,
                'last_order_at' => $r->last_order_at,
            ])->values()->all(),
        ];
    }
}
