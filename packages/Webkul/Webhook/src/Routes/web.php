<?php

use Illuminate\Support\Facades\Route;
use Webkul\Webhook\Http\Controllers\WebhookLogsController;
use Webkul\Webhook\Http\Controllers\WebhookSettingsController;

/**
 * Catalog routes.
 */
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function (): void {
    Route::prefix('configuration/webhook')->group(function (): void {
        Route::controller(WebhookSettingsController::class)->group(function (): void {
            Route::get('', 'index')->name('webhook.settings.index');
            Route::post('/', 'store')->name('webhook.settings.store');
            Route::get('form-data', 'listSettings')->name('webhook.settings.get');
        });

        Route::controller(WebhookLogsController::class)->prefix('logs')->group(function (): void {
            Route::get('', 'index')->name('webhook.logs.index');
            Route::get('show/{id}', 'show')->name('webhook.logs.show')->whereNumber('id');
            Route::delete('delete/{id}', 'destroy')->name('webhook.logs.delete')->whereNumber('id');
            Route::post('mass-delete', 'massDestroy')->name('webhook.logs.mass_delete');
        });
    });
});
