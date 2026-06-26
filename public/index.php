<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/

if (! file_exists(__DIR__.'/../vendor/autoload.php')) {
    $base = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $base = $base === '/' || $base === '\\' ? '' : rtrim($base, '/');

    header('Location: '.$base.'/install.php');
    exit;
}

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
