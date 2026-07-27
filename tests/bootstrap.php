<?php

/**
 * The container exports its own values from .env, and PHPUnit's <env> entries do
 * not win over them once Dotenv has loaded. Booting the suite as "local" makes
 * Application::runningUnitTests() false, so PreventRequestForgery rejects every
 * write request; the driver entries matter just as much, since without them the
 * suite runs against the real redis queue, redis cache and SMTP host instead of
 * the in-memory fakes. These must be set before the framework resolves them.
 */
$overrides = [
    'APP_ENV'           => 'testing',
    'APP_DEBUG'         => 'true',
    'BCRYPT_ROUNDS'     => '4',
    'CACHE_STORE'       => 'array',
    'CACHE_DRIVER'      => 'array',
    'MAIL_MAILER'       => 'array',
    'QUEUE_CONNECTION'  => 'sync',
    'SESSION_DRIVER'    => 'array',
    'TELESCOPE_ENABLED' => 'false',
    // The e2e app .env raises this so Playwright's many logins do not throttle;
    // the throttle tests need the real low limit.
    'ADMIN_LOGIN_RATE_LIMIT' => '5',
];

foreach ($overrides as $key => $value) {
    putenv("{$key}={$value}");

    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
