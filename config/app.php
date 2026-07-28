<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'UnoPim'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Admin URL
    |--------------------------------------------------------------------------
    */

    'admin_url' => env('APP_ADMIN_URL', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Allowed IPs
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of IPs that can access the application during
    | maintenance mode.
    |
    */

    'maintenance_allowed_ips' => env('MAINTENANCE_ALLOWED_IPS'),

    /*
    |--------------------------------------------------------------------------
    | Debug Allowed IPs
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of IPs that can see the debug bar. When set,
    | debug bar is hidden from all other IPs even if APP_DEBUG is true.
    |
    */

    'debug_allowed_ips' => env('APP_DEBUG_ALLOWED_IPS'),

    /*
    |--------------------------------------------------------------------------
    | API Rate Limit
    |--------------------------------------------------------------------------
    |
    | Requests per minute allowed by the "api" named limiter, keyed by the
    | authenticated user when there is one and by client IP otherwise.
    |
    */

    'api_rate_limit' => (int) env('API_RATE_LIMIT', 60),

    /*
    |--------------------------------------------------------------------------
    | Trusted Hosts
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of hostnames accepted in the Host header in addition
    | to APP_URL and its subdomains. Any other host is rejected with a 400,
    | which is what keeps Host / X-Forwarded-Host poisoning out.
    |
    */

    'trusted_hosts' => env('TRUSTED_HOSTS'),

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of proxy IPs (or CIDRs) allowed to set X-Forwarded-*
    | headers. Use '*' only when the edge already strips those headers from
    | public traffic.
    |
    | Read from a booting callback in bootstrap/app.php: TrustProxies takes a
    | value rather than a closure, and the middleware configuration callback
    | runs before the environment and the config files are loaded, so anything
    | read there would always fall back to the default below.
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES', '127.0.0.1'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    */

    'locale' => env('APP_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    */

    'fallback_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Base Currency Code
    |--------------------------------------------------------------------------
    */

    'currency' => env('APP_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Default channel Code
    |--------------------------------------------------------------------------
    */

    'channel' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store'  => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
