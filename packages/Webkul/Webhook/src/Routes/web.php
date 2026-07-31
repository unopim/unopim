<?php

use Illuminate\Support\Facades\Route;
use Webkul\Webhook\Http\Controllers\WebhookController;
use Webkul\Webhook\Http\Controllers\WebhookLogsController;

Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function (): void {
    Route::prefix('configuration/webhook')->group(function (): void {
        Route::controller(WebhookController::class)->group(function (): void {
            Route::get('', 'index')->name('webhook.index');
            Route::post('create', 'store')->name('webhook.store');
            Route::get('edit/{id}', 'edit')->name('webhook.edit')->whereNumber('id');
            Route::put('edit/{id}', 'update')->name('webhook.update')->whereNumber('id');
            Route::delete('delete/{id}', 'destroy')->name('webhook.delete')->whereNumber('id');
            Route::post('mass-delete', 'massDestroy')->name('webhook.mass_delete');
            Route::post('test', 'test')->name('webhook.test');
        });

        Route::controller(WebhookLogsController::class)->prefix('logs')->group(function (): void {
            Route::get('', 'index')->name('webhook.logs.index');
            Route::get('webhook/{id}', 'forWebhook')->name('webhook.logs.for-webhook')->whereNumber('id');
            Route::get('show/{id}', 'show')->name('webhook.logs.show')->whereNumber('id');
            Route::delete('delete/{id}', 'destroy')->name('webhook.logs.delete')->whereNumber('id');
            Route::post('mass-delete', 'massDestroy')->name('webhook.logs.mass_delete');
        });
    });
});
