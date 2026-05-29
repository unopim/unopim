<?php

declare(strict_types=1);

return [
    'access_token_ttl'  => env('ACCESS_TOKEN_TTL', 3600),
    'refresh_token_ttl' => env('REFRESH_TOKEN_TTL', 3600),
];
