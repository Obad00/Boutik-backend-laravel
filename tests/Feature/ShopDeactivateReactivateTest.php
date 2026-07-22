<?php

use App\Models\Shop;
use Database\Seeders\DemoDataSeeder;

beforeEach(function () {
    $this->seed(DemoDataSeeder::class);
});

test('deactivating a shop blocks its login, reactivating restores it', function () {
    $superadminLogin = $this->postJson('/api/superadmin/login', [
        'email' => 'admin@boutik.sn',
        'password' => 'changeme123',
    ])->assertOk()->json();
    $superadminToken = $superadminLogin['token'];

    // 1. Créer une boutique de test.
    $created = $this->withToken($superadminToken)->postJson('/api/superadmin/shops', [
        'name' => 'Boutik Test Désactivation',
        'address' => 'Rue de Test, Dakar',
        'phone' => '77 000 00 00',
    ])->assertCreated()->json();

    $shopId = $created['shop']['id'];
    $code = $created['code'];

    // Le login fonctionne tant que la boutique est active.
    $this->postJson('/api/auth/shop-login', ['code' => $code])->assertOk();

    // 2. Désactiver -> disparaît de la liste "actives", apparaît comme inactive.
    $this->app['auth']->forgetGuards();
    $this->withToken($superadminToken)
        ->deleteJson("/api/superadmin/shops/{$shopId}")
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(Shop::find($shopId)->is_active)->toBeFalse();

    $shops = $this->withToken($superadminToken)->getJson('/api/superadmin/shops')->assertOk()->json();
    $listed = collect($shops)->firstWhere('id', $shopId);
    expect($listed)->not->toBeNull();
    expect($listed['is_active'])->toBeFalse();

    // 3. Le login par code doit être refusé pendant que la boutique est désactivée.
    $this->postJson('/api/auth/shop-login', ['code' => $code])
        ->assertUnauthorized()
        ->assertJson(['error' => 'Boutique introuvable ou désactivée']);

    // 4. Réactiver.
    $this->app['auth']->forgetGuards();
    $reactivated = $this->withToken($superadminToken)
        ->postJson("/api/superadmin/shops/{$shopId}/reactivate")
        ->assertOk()
        ->json();

    expect($reactivated['is_active'])->toBeTrue();
    expect(Shop::find($shopId)->is_active)->toBeTrue();

    // 5. Le login refonctionne avec le même code, sans avoir besoin d'en régénérer un.
    $this->postJson('/api/auth/shop-login', ['code' => $code])
        ->assertOk()
        ->assertJsonPath('shop.id', $shopId);
});
