<?php

namespace App\Http\Middleware;
// app/Http/Middleware/IpWhitelistMiddleware.php

use Closure;
use Illuminate\Http\Request;

class IpWhitelistMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $allowed = config('api.whitelisted_ips', []);
        // Als de whitelist leeg is, skippen we de check
        if (!empty($allowed) && ! in_array($request->ip(), $allowed)) {
            return response()->json(['message' => 'Forbidden IP'], 403);
        }
        return $next($request);
    }
}
