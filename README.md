# Boutik — Backend Laravel

API REST pour Boutik, l'app de gestion pour boutiques de quartier. Remplace le backend Node/Express de référence (`boutik-backend/`, non conservé) pour tourner chez un hébergeur qui ne supporte que PHP/Laravel.

Architecture multi-tenant : chaque boutique (`shop_id`) est totalement isolée des autres. Le frontend (`../boutik/`, React/Vite) consomme cette API via `boutik/src/services/api/*`.

## Stack

- Laravel 12 / PHP 8.2+
- MySQL (InnoDB)
- Laravel Sanctum (tokens Bearer, pas de sessions/cookies — le frontend est une SPA séparée)
- Pest pour les tests

## Architecture — points clés

- **Deux guards Sanctum totalement séparés** : `ShopOwner` (compte boutique) et `Superadmin` sont deux modèles Eloquent distincts, chacun avec ses propres tokens. Un token boutique ne peut structurellement jamais authentifier une route superadmin (et inversement) — voir `App\Http\Middleware\EnsureShopOwner` / `EnsureSuperadmin`.
- **Isolation par `shop_id` automatique** : tout modèle shop-scopé étend `App\Models\ShopScopedModel`, qui applique un global scope (`App\Models\Scopes\ShopScope`) filtrant systématiquement sur le `shop_id` de l'utilisateur connecté (`App\Support\ShopContext`, injecté par `EnsureShopOwner`). Aucun contrôleur ne lit jamais `shop_id` depuis la requête du client.
- **Identifiants** : PK `VARCHAR(40)` au format `prefix_xxxxxxx` (ex. `prod_a1b2c3d`), générés par `App\Support\Ids::generate()` sur l'event `creating` de chaque modèle (trait `HasPrefixedId`).
- **Vente (`POST /api/orders`)** : une transaction unique (`DB::transaction`) avec verrou pessimiste (`lockForUpdate()`) sur chaque produit avant toute écriture — la vente échoue proprement (409) si le stock est insuffisant, sans rien avoir modifié.
- **Désactivation de boutique** : toujours logique (`is_active = 0`), jamais de `DELETE` réel sur `shops` — un vrai delete casserait la cascade InnoDB entre `categories → products` (CASCADE) et `products.category_id → categories` (RESTRICT).
- **PIN admin** : jamais renvoyé en clair au frontend. Stocké hashé (`settings.admin_pin_hash`, bcrypt via `Hash::make`), vérifié côté serveur par `POST /api/auth/verify-admin-pin`.

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configurer une base MySQL dans `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), puis :

```bash
php artisan migrate
php artisan db:seed --class=DemoDataSeeder
```

Le seeder crée :

| Compte | Identifiants |
|---|---|
| Superadmin | `admin@boutik.sn` / `changeme123` |
| Boutique SANDAGA | code `SANDAGA`, PIN admin `1234` |
| Boutique THIES | code `THIES`, PIN admin `1234` |

### Comptes superadmin de production

`DemoDataSeeder` embarque un superadmin de démo — à ne pas utiliser en production. Pour provisionner les vrais comptes superadmin, séparément et sans toucher aux données de boutiques :

```bash
SUPERADMIN_PASSWORD_1=... SUPERADMIN_PASSWORD_2=... php artisan db:seed --class=SuperadminSeeder
```

Idempotent (relançable sans dupliquer ni faire tourner silencieusement un mot de passe déjà en place). Si les variables d'env sont omises lors de la création initiale, un mot de passe aléatoire est généré et affiché une seule fois dans la console — à noter immédiatement. Voir `database/seeders/SuperadminSeeder.php` pour le détail.

## Lancer le serveur

```bash
php artisan serve
```

API disponible sur `http://localhost:8000/api`. Le frontend Vite doit pointer dessus via `VITE_API_URL` (voir `../boutik/.env`), et `FRONTEND_URL` dans ce `.env` doit correspondre à l'origine du frontend (CORS, `config/cors.php`).

## Tests

```bash
php artisan test
```

Tourne automatiquement sur SQLite en mémoire (`phpunit.xml`), aucune base MySQL requise. `tests/Feature/ShopFlowAndIsolationTest.php` couvre le scénario métier de référence : login boutique → vente à crédit (stock décrémenté + dette client augmentée) → remboursement de crédit (dette réduite + mouvement de caisse créé) → vérifie qu'un token d'une autre boutique ne voit aucune de ces données, et qu'un token superadmin ne peut pas authentifier une route boutique.

## API

Toutes les routes shop-scopées exigent `Authorization: Bearer <token boutique>` (middleware `shop.auth`) ; les routes `/superadmin/*` exigent un token superadmin (`superadmin.auth`). Les deux types de token ne sont jamais interchangeables.

**Auth**
- `POST /api/auth/shop-login` `{code}` → `{token, shop, owner}`
- `POST /api/auth/verify-admin-pin` `{pin}` *(shop.auth)* → `{ok: true}`
- `POST /api/superadmin/login` `{email, password}` → `{token, superadmin}`

**Boutique** *(shop.auth)*
- `GET/POST /api/products`, `PUT/DELETE /api/products/{id}`, `POST /api/products/{id}/adjust-stock` `{delta}`
- `GET/POST /api/categories`, `PUT/DELETE /api/categories/{id}`
- `GET/POST /api/customers`, `PUT/DELETE /api/customers/{id}`
- `GET/POST /api/orders` — voir la transaction critique ci-dessus
- `GET /api/stock-movements`, `POST /api/stock-movements/restock` `{product_id, quantity}`
- `GET/POST /api/cash-movements` `{type, amount, reason}`
- `GET/POST /api/credit-payments` `{customer_id, amount}`
- `GET/PUT /api/settings`

**Superadmin** *(superadmin.auth)*
- `GET/POST /api/superadmin/shops`, `PUT/DELETE /api/superadmin/shops/{id}` (DELETE = désactivation logique)

## Notes pour la suite

- Créer une boutique via `POST /api/superadmin/shops` provisionne aussi un `ShopOwner` par défaut (obligatoire pour que le login par code fonctionne immédiatement) — voir `App\Http\Controllers\Superadmin\ShopController::store`.
- Pas de route `GET /{id}` sur products/categories/customers (fidèle au contrat historique) : le frontend recompose ces lookups depuis `list()` côté client.
