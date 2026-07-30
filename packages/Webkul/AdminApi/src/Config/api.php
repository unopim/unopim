<?php

return [
    'access_token_ttl'  => env('ACCESS_TOKEN_TTL', 3600),
    'refresh_token_ttl' => env('REFRESH_TOKEN_TTL', 3600),
    'rate_limit'        => env('REST_API_RATE_LIMIT', 120),
    'token_rate_limit'  => env('OAUTH_TOKEN_RATE_LIMIT', 10),
    'docs_url'          => env('UNOPIM_API_DOCS_URL', 'https://devdocs.unopim.com/master/api/'),

    'structure_cache' => [
        'enabled' => env('UNOPIM_API_STRUCTURE_CACHE', true),
        'ttl'     => env('UNOPIM_API_STRUCTURE_CACHE_TTL', 3600),
    ],
];
