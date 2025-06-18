<?php

// app/Http/Middleware/EnsureHttpsMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureHttpsMiddleware
{
    
public function handle(Request $request, Closure $next)
{
    // Skip HTTPS-check in local
    if (app()->environment('local')) {
        return $next($request);
    }

    if (! $request->isSecure()) {
        return response()->json(['message' => 'HTTPS Required'], 403);
    }

    return $next($request);
}
}

