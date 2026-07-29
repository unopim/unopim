<?php

$cacheBypass = __DIR__.'/../bootstrap/cache/never-cached-in-tests.php';

/**
 * Set before Dotenv loads, since PHPUnit <env> entries don't override .env: "local" env breaks CSRF/forgery
 * guards in tests, and the driver entries keep the suite on in-memory fakes instead of real redis/SMTP.
 */
$overrides = [
    'APP_ENV'           => 'testing',
    'APP_DEBUG'         => 'true',
    'APP_CONFIG_CACHE'  => $cacheBypass,
    'APP_ROUTES_CACHE'  => $cacheBypass,
    'BCRYPT_ROUNDS'     => '4',
    'CACHE_STORE'       => 'array',
    'CACHE_DRIVER'      => 'array',
    'MAIL_MAILER'       => 'array',
    'QUEUE_CONNECTION'  => 'sync',
    'SESSION_DRIVER'    => 'array',
    'TELESCOPE_ENABLED' => 'false',
    // Restore the real low limit that throttle tests need; the e2e .env raises it for Playwright's many logins.
    'ADMIN_LOGIN_RATE_LIMIT' => '5',
];

foreach ($overrides as $key => $value) {
    putenv("{$key}={$value}");

    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
