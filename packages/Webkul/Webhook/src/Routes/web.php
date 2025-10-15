<?php

use Illuminate\Support\Facades\Route;
use Webkul\Webhook\Http\Controllers\WebhookLogsController;
use Webkul\Webhook\Http\Controllers\WebhookSettingsController;

/**
 * Catalog routes.
 */
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('webhook')->group(function () {
        Route::controller(WebhookSettingsController::class)->prefix('settings')->group(function () {
            Route::get('', 'index')->name('webhook.settings.index');
            Route::post('/', 'store')->name('webhook.settings.store');
            Route::get('form-data', 'listSettings')->name('webhook.settings.get');
        });

        Route::get('history', [WebhookSettingsController::class, 'listHistory'])->name('webhook.settings.history.get');

        Route::controller(WebhookLogsController::class)->prefix('logs')->group(function () {
            Route::get('', 'index')->name('webhook.logs.index');
            Route::delete('delete/{id}', 'destroy')->name('webhook.logs.delete');
            Route::post('mass-delete', 'massDestroy')->name('webhook.logs.mass_delete');
        });
    });
});
