<?php
// config/api.php
return [

    // Haal je API-key uit je .env
    'api_key' => env('API_KEY'),
    'import_user_id'  => env('IMPORT_USER_ID'),

    // Optionele lijst van toegestane IP-adressen (comma-separated in .env)
   /* 'whitelisted_ips' => env('WHITELISTED_IPS', '')
        ? explode(',', env('WHITELISTED_IPS'))
        : [],
*/
];
