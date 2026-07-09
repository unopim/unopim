<?php

return [
    'access_token_ttl'  => env('ACCESS_TOKEN_TTL', 3600),
    'refresh_token_ttl' => env('REFRESH_TOKEN_TTL', 3600),
    'rate_limit'        => env('REST_API_RATE_LIMIT', 120),
];
