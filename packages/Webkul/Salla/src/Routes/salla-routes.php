<?php

use Illuminate\Support\Facades\Route;
use Webkul\Salla\Http\Controllers\CredentialController;
use Webkul\Salla\Http\Controllers\ImportMappingController;
use Webkul\Salla\Http\Controllers\MappingController;
use Webkul\Salla\Http\Controllers\OptionController;
use Webkul\Salla\Http\Controllers\SettingController;

/**
 * Salla routes.
 */
Route::group(['middleware' => ['web', 'admin', 'tenant'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('salla')->group(function () {

        Route::controller(CredentialController::class)->prefix('credentials')->group(function () {
            Route::get('', 'index')->name('salla.credentials.index');
            Route::post('create', 'store')->name('salla.credentials.store');
            Route::get('edit/{id}', 'edit')->name('salla.credentials.edit');
            Route::put('update/{id}', 'update')->name('salla.credentials.update');
            Route::delete('delete/{id}', 'destroy')->name('salla.credentials.delete');
        });

        Route::prefix('export')->group(function () {
            Route::controller(SettingController::class)->prefix('settings')->group(function () {
                Route::get('{id}', 'index')->name('admin.salla.settings');
                Route::post('create', 'store')->name('salla.export-settings.create');
            });
            Route::controller(MappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.salla.export-mappings');
                Route::post('create', 'store')->name('salla.export-mappings.create');
            });
        });

        Route::prefix('import')->group(function () {
            Route::controller(ImportMappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.salla.import-mappings');
                Route::post('create', 'store')->name('salla.import-mappings.create');
            });
        });

        Route::controller(OptionController::class)->group(function () {
            Route::get('get-attribute', 'listAttributes')->name('admin.salla.get-attribute');
            Route::get('get-image-attribute', 'listImageAttributes')->name('admin.salla.get-image-attribute');
            Route::get('get-gallery-attribute', 'listGalleryAttributes')->name('admin.salla.get-gallery-attribute');
            Route::get('get-salla-credentials', 'listSallaCredential')->name('salla.credential.fetch-all');
            Route::get('get-salla-channel', 'listChannel')->name('salla.channel.fetch-all');
            Route::get('get-salla-currency', 'listCurrency')->name('salla.currency.fetch-all');
            Route::get('get-salla-locale', 'listLocale')->name('salla.locale.fetch-all');
            Route::get('get-salla-attrGroup', 'listAttributeGroup')->name('salla.attribute-group.fetch-all');
            Route::get('get-salla-family', 'listSallaFamily')->name('admin.salla.get-all-family-variants');
        });

    });
});
