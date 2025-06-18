<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{

    protected $except = [
        'orders*',
        'woo-import/*',
        'api/woo-import/*',   // <— misten we nog
    ];

}
