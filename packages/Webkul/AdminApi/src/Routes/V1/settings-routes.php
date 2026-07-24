<?php

use Illuminate\Support\Facades\Route;
use Webkul\AdminApi\Http\Controllers\API\Settings\ChannelController;
use Webkul\AdminApi\Http\Controllers\API\Settings\CurrencyController;
use Webkul\AdminApi\Http\Controllers\API\Settings\LocaleController;

Route::group([
    'middleware' => [
        'auth:api',
    ],
], function () {
    /** Locales API Route Routes */
    Route::controller(LocaleController::class)->prefix('locales')->group(function () {
        Route::get('', 'index')->name('admin.api.locales.index');
        Route::get('{code}', 'get')->name('admin.api.locales.get');
        Route::post('', 'store')->name('admin.api.locales.store');
        Route::put('{code}', 'update')->name('admin.api.locales.update');
        Route::delete('{code}', 'delete')->name('admin.api.locales.delete');
    });

    /** Channels API Route Routes */
    Route::controller(ChannelController::class)->prefix('channels')->group(function () {
        Route::get('', 'index')->name('admin.api.channels.index');
        Route::get('{code}', 'get')->name('admin.api.channels.get');
        Route::post('', 'store')->name('admin.api.channels.store');
        Route::put('{code}', 'update')->name('admin.api.channels.update');
        Route::delete('{code}', 'delete')->name('admin.api.channels.delete');
    });

    /** Currencies API Route Routes */
    Route::controller(CurrencyController::class)->prefix('currencies')->group(function () {
        Route::get('', 'index')->name('admin.api.currencies.index');
        Route::get('{code}', 'get')->name('admin.api.currencies.get');
        Route::post('', 'store')->name('admin.api.currencies.store');
        Route::put('{code}', 'update')->name('admin.api.currencies.update');
        Route::delete('{code}', 'delete')->name('admin.api.currencies.delete');
    });
});
