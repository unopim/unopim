<?php

use Illuminate\Support\Facades\Route;
use Webkul\AdminApi\Http\Controllers\Integrations\ApiKeysController;

/**
 * Settings routes.
 */
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('configuration/integrations')->group(function () {

        /**
         * API keys routes.
         */
        Route::controller(ApiKeysController::class)->group(function () {
            Route::get('', 'index')->name('admin.configuration.integrations.index');

            Route::get('create', 'create')->name('admin.configuration.integrations.create');

            Route::post('create', 'store')->name('admin.configuration.integrations.store');

            Route::get('edit/{id}', 'edit')->name('admin.configuration.integrations.edit')->whereNumber('id');

            Route::put('edit/{id}', 'update')->name('admin.configuration.integrations.update')->whereNumber('id');

            Route::delete('edit/{id}', 'destroy')->name('admin.configuration.integrations.delete')->whereNumber('id');

            Route::post('generate', 'generateKey')->name('admin.configuration.integrations.generate_key');

            Route::post('re-generate-secrete', 'regenerateSecretKey')->name('admin.configuration.integrations.re_generate_secret_key');

            Route::post('re-generate-password', 'regeneratePassword')->name('admin.configuration.integrations.re_generate_password');
        });
    });
});
