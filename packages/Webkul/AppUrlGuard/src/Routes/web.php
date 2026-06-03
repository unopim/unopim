<?php

use Illuminate\Support\Facades\Route;
use Webkul\AppUrlGuard\Http\Controllers\AppUrlGuardController;

/*
 * APP_URL guard route. Registered only in debug mode by the package provider.
 * Used by the warning modal to re-validate APP_URL on dismiss.
 */
Route::middleware('web')
    ->get('app-url-guard/check', [AppUrlGuardController::class, 'check'])
    ->name('app_url_guard.check');
