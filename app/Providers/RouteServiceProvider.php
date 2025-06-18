<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Dit is de “home” route waar gebruikers naar worden geleid na inloggen.
     */
    public const HOME = '/home';

    /**
     * Hier kun je model-bindingen en pattern-constraints registreren.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // LAAD API-ROUTES (zonder CSRF)
            Route::prefix('api')
                 ->middleware('api')
                 ->group(base_path('routes/api.php'));

            // LAAD WEB-ROUTES (met CSRF / sessies)
            Route::middleware('web')
                 ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Stel hier je rate limiting in voor API-aanroepen.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // bijv. max. 60 calls per minuut per IP
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
