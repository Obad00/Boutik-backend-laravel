<?php

use Database\Seeders\DemoDataSeeder;

beforeEach(function () {
    $this->seed(DemoDataSeeder::class);
});

test('a shop owner token gets 401 on /api/superadmin/stats, never the payload', function () {
    $login = $this->postJson('/api/auth/shop-login', ['code' => 'SANDAGA'])
        ->assertOk()
        ->json();

    $this->withToken($login['token'])
        ->getJson('/api/superadmin/stats')
        ->assertUnauthorized()
        ->assertJsonMissingPath('overview')
        ->assertJsonMissingPath('rankings');
});

test('an unauthenticated request gets 401 on /api/superadmin/stats', function () {
    $this->getJson('/api/superadmin/stats')->assertUnauthorized();
});

test('a superadmin token receives the full platform stats payload', function () {
    $login = $this->postJson('/api/superadmin/login', [
        'email' => 'admin@boutik.sn',
        'password' => 'changeme123',
    ])->assertOk()->json();

    $this->withToken($login['token'])
        ->getJson('/api/superadmin/stats')
        ->assertOk()
        ->assertJsonStructure([
            'overview' => [
                'shops_total', 'shops_active', 'shops_inactive',
                'shops_new_this_week', 'shops_new_this_month',
                'revenue_total', 'revenue_cash', 'revenue_credit',
                'sales_count', 'average_basket', 'total_debt', 'stock_value_total',
            ],
            'trends' => [
                'new_shops_by_month', 'revenue_by_day', 'revenue_by_month', 'sales_count_by_day',
            ],
            'rankings' => [
                'top_shops_by_revenue', 'top_shops_by_sales_count',
                'shops_never_sold', 'shops_inactive_14d',
            ],
        ]);
});

test('shops with no orders show up as never sold, not as inactive-14d churn', function () {
    // Le seeder crée SANDAGA/THIES sans aucune commande : les deux doivent
    // apparaître dans shops_never_sold, et dans aucune autre liste de
    // classement (listes disjointes, validé avec l'utilisateur).
    $login = $this->postJson('/api/superadmin/login', [
        'email' => 'admin@boutik.sn',
        'password' => 'changeme123',
    ])->assertOk()->json();

    $rankings = $this->withToken($login['token'])
        ->getJson('/api/superadmin/stats')
        ->assertOk()
        ->json('rankings');

    $neverSoldNames = collect($rankings['shops_never_sold'])->pluck('shop_name');
    $inactive14dNames = collect($rankings['shops_inactive_14d'])->pluck('shop_name');

    expect($neverSoldNames)->toContain('Boutik Sandaga', 'Boutik Thiès Centre');
    expect($inactive14dNames->intersect($neverSoldNames))->toBeEmpty();
});
