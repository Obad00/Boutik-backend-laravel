<?php

use App\Models\CashMovement;
use App\Models\Customer;
use App\Models\Product;
use Database\Seeders\DemoDataSeeder;

beforeEach(function () {
    $this->seed(DemoDataSeeder::class);
});

test('credit sale, partial repayment, and cross-shop isolation', function () {
    $sandagaLogin = $this->postJson('/api/auth/shop-login', ['code' => 'SANDAGA'])
        ->assertOk()
        ->json();

    $sandagaToken = $sandagaLogin['token'];
    $sandagaShopId = $sandagaLogin['shop']['id'];

    $product = Product::where('shop_id', $sandagaShopId)->firstOrFail();
    $customer = Customer::where('shop_id', $sandagaShopId)->firstOrFail();
    $stockBefore = (float) $product->stock_quantity;
    $debtBefore = (float) $customer->current_debt;

    $order = $this->withToken($sandagaToken)->postJson('/api/orders', [
        'items' => [[
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => $product->price_sell,
        ]],
        'total' => $product->price_sell * 2,
        'payment_mode' => 'credit',
        'customer_id' => $customer->id,
    ])->assertCreated()->json();

    expect((float) Product::find($product->id)->stock_quantity)->toEqual($stockBefore - 2);
    expect((float) Customer::find($customer->id)->current_debt)->toEqual($debtBefore + $order['total']);

    // Vente à crédit sans client -> refusée.
    $this->withToken($sandagaToken)->postJson('/api/orders', [
        'items' => [[
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => $product->price_sell,
        ]],
        'total' => $product->price_sell,
        'payment_mode' => 'credit',
    ])->assertStatus(400);

    // Survente -> 409, rien n'est écrit.
    $this->withToken($sandagaToken)->postJson('/api/orders', [
        'items' => [[
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 999999,
            'unit_price' => $product->price_sell,
        ]],
        'total' => $product->price_sell * 999999,
        'payment_mode' => 'cash',
    ])->assertStatus(409);
    expect((float) Product::find($product->id)->stock_quantity)->toEqual($stockBefore - 2);

    // Remboursement partiel du crédit.
    $debtAfterSale = (float) Customer::find($customer->id)->current_debt;

    $this->withToken($sandagaToken)->postJson('/api/credit-payments', [
        'customer_id' => $customer->id,
        'amount' => 5,
    ])->assertCreated();

    expect((float) Customer::find($customer->id)->current_debt)->toEqual(max(0, $debtAfterSale - 5));
    expect(
        CashMovement::where('shop_id', $sandagaShopId)
            ->where('reason', 'like', 'Remboursement crédit%')
            ->exists()
    )->toBeTrue();

    // --- THIES ne doit jamais voir les données de SANDAGA ---
    $thiesLogin = $this->postJson('/api/auth/shop-login', ['code' => 'THIES'])
        ->assertOk()
        ->json();
    $thiesToken = $thiesLogin['token'];

    // Sanctum's RequestGuard memoizes the resolved user for the container's
    // lifetime — within a single test, switching bearer tokens needs a fresh
    // guard or every subsequent call keeps resolving as the SANDAGA owner.
    // Not a production concern: a real HTTP request is a fresh process/container.
    $this->app['auth']->forgetGuards();

    $thiesProductIds = collect($this->withToken($thiesToken)->getJson('/api/products')->assertOk()->json())->pluck('id');
    $thiesCustomerIds = collect($this->withToken($thiesToken)->getJson('/api/customers')->assertOk()->json())->pluck('id');
    $thiesOrderIds = collect($this->withToken($thiesToken)->getJson('/api/orders')->assertOk()->json())->pluck('id');

    expect($thiesProductIds)->not->toContain($product->id);
    expect($thiesCustomerIds)->not->toContain($customer->id);
    expect($thiesOrderIds)->not->toContain($order['id']);

    // Preuve que c'est le scope Eloquent (pas juste le filtrage de liste) qui protège :
    // un accès direct par id à une ressource SANDAGA avec un token THIES doit 404.
    $this->withToken($thiesToken)
        ->putJson("/api/products/{$product->id}", ['name' => 'Hack'])
        ->assertNotFound();

    // Un token boutique ne doit jamais pouvoir authentifier une route superadmin.
    $this->app['auth']->forgetGuards();
    $this->withToken($sandagaToken)
        ->getJson('/api/superadmin/shops')
        ->assertUnauthorized();
});

test('superadmin token cannot authenticate shop-scoped routes', function () {
    $superadminLogin = $this->postJson('/api/superadmin/login', [
        'email' => 'admin@boutik.sn',
        'password' => 'changeme123',
    ])->assertOk()->json();

    $this->withToken($superadminLogin['token'])
        ->getJson('/api/products')
        ->assertUnauthorized();
});
