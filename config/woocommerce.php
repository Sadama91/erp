<?php
return [
    'url'               => env('WC_STORE_URL'),
    'consumer_key'      => env('WC_CONSUMER_KEY'),
    'consumer_secret'   => env('WC_CONSUMER_SECRET'),
    'version'           => env('WC_API_VERSION', 'wc/v3'),
    'verify_ssl'        => (bool) env('WC_VERIFY_SSL', false),
    'query_string_auth' => true,
];
