<?php
// app/Http/Kernel.php

protected $routeMiddleware = [
    // ...
    'api.key'        => \App\Http\Middleware\ApiKeyMiddleware::class,
    'ip.whitelist'   => \App\Http\Middleware\IpWhitelistMiddleware::class,
    'ensure.https'   => \App\Http\Middleware\EnsureHttpsMiddleware::class,
];
