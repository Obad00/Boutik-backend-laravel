<?php

use App\Exceptions\HttpException;
use App\Http\Middleware\EnsureShopOwner;
use App\Http\Middleware\EnsureSuperadmin;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'shop.auth' => EnsureShopOwner::class,
            'superadmin.auth' => EnsureSuperadmin::class,
        ]);

        // Must resolve before SubstituteBindings, otherwise implicit route-model
        // binding on shop-scoped models would run before ShopContext exists and
        // the ShopScope global scope would silently no-op (cross-shop leak).
        $middleware->priority([
            EnsureShopOwner::class,
            EnsureSuperadmin::class,
            SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->status);
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return response()->json(['error' => 'Ressource introuvable'], 404);
        });

        $exceptions->render(function (QueryException $e) {
            report($e);

            return response()->json(['error' => 'Erreur interne du serveur'], 500);
        });

        $exceptions->render(function (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            return response()->json(['error' => $first ?? 'Requête invalide'], 422);
        });

        $exceptions->render(function (HttpExceptionInterface $e) {
            return response()->json(['error' => $e->getMessage() ?: 'Erreur'], $e->getStatusCode());
        });
    })->create();
