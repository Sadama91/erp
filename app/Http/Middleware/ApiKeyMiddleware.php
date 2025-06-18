<?php
// app/Http/Middleware/ApiKeyMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');
        if (!$apiKey || $apiKey !== config('api.api_key')) {
            return response()->json(['message' => 'Invalid API Key'], 401);
        }
        return $next($request);
    }
}
