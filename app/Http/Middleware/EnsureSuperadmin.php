<?php

namespace App\Http\Middleware;

use App\Models\Superadmin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('sanctum') instanceof Superadmin, 401, 'Authentification superadmin requise');

        return $next($request);
    }
}
