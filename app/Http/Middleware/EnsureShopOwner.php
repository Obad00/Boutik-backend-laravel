<?php

namespace App\Http\Middleware;

use App\Models\ShopOwner;
use App\Support\ShopContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        abort_unless($user instanceof ShopOwner, 401, 'Authentification requise');
        abort_unless($user->shop && $user->shop->is_active, 403, 'Boutique introuvable ou désactivée');

        app()->instance(ShopContext::class, new ShopContext($user->shop_id));

        return $next($request);
    }
}
