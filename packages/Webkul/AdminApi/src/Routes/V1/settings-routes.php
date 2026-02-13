<?php

use Illuminate\Support\Facades\Route;
use Webkul\AdminApi\Http\Controllers\API\Settings\ChannelController;
use Webkul\AdminApi\Http\Controllers\API\Settings\CurrencyController;
use Webkul\AdminApi\Http\Controllers\API\Settings\LocaleController;

Route::group([], function () {
    /** Locales API Route Routes */
    Route::controller(LocaleController::class)->prefix('locales')->group(function () {
        Route::get('', 'index')->name('admin.api.locales.index');
        Route::get('{code}', 'get')->name('admin.api.locales.get');
    });

    /** Channels API Route Routes */
    Route::controller(ChannelController::class)->prefix('channels')->group(function () {
        Route::get('', 'index')->name('admin.api.channels.index');
        Route::get('{code}', 'get')->name('admin.api.channels.get');
    });

    /** Currencies API Route Routes */
    Route::controller(CurrencyController::class)->prefix('currencies')->group(function () {
        Route::get('', 'index')->name('admin.api.currencies.index');
        Route::get('{code}', 'get')->name('admin.api.currencies.get');
    });
});
