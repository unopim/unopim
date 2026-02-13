<?php

use Illuminate\Support\Facades\Route;
use Webkul\AdminApi\Http\Controllers\Integrations\ApiKeysController;

/**
 * Settings routes.
 */
Route::group(['middleware' => ['admin', 'tenant'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('integrations')->group(function () {

        /**
         * API keys routes.
         */
        Route::controller(ApiKeysController::class)->prefix('api-keys')->group(function () {
            Route::get('', 'index')->name('admin.configuration.integrations.index');

            Route::get('create', 'create')->name('admin.configuration.integrations.create');

            Route::post('create', 'store')->name('admin.configuration.integrations.store');

            Route::get('edit/{id}', 'edit')->name('admin.configuration.integrations.edit');

            Route::put('edit/{id}', 'update')->name('admin.configuration.integrations.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.configuration.integrations.delete');

            Route::post('generate', 'generateKey')->name('admin.configuration.integrations.generate_key');

            Route::post('re-generate-secrete', 'regenerateSecretKey')->name('admin.configuration.integrations.re_generate_secret_key');
        });
    });
});
