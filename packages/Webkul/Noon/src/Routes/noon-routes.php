<?php

use Illuminate\Support\Facades\Route;
use Webkul\Noon\Http\Controllers\CredentialController;
use Webkul\Noon\Http\Controllers\ImportMappingController;
use Webkul\Noon\Http\Controllers\MappingController;
use Webkul\Noon\Http\Controllers\OptionController;
use Webkul\Noon\Http\Controllers\SettingController;

/**
 * Noon routes.
 */
Route::group(['middleware' => ['web', 'admin', 'tenant'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('noon')->group(function () {

        Route::controller(CredentialController::class)->prefix('credentials')->group(function () {
            Route::get('', 'index')->name('noon.credentials.index');
            Route::post('create', 'store')->name('noon.credentials.store');
            Route::get('edit/{id}', 'edit')->name('noon.credentials.edit');
            Route::put('update/{id}', 'update')->name('noon.credentials.update');
            Route::delete('delete/{id}', 'destroy')->name('noon.credentials.delete');
        });

        Route::prefix('export')->group(function () {
            Route::controller(SettingController::class)->prefix('settings')->group(function () {
                Route::get('{id}', 'index')->name('admin.noon.settings');
                Route::post('create', 'store')->name('noon.export-settings.create');
            });
            Route::controller(MappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.noon.export-mappings');
                Route::post('create', 'store')->name('noon.export-mappings.create');
            });
        });

        Route::prefix('import')->group(function () {
            Route::controller(ImportMappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.noon.import-mappings');
                Route::post('create', 'store')->name('noon.import-mappings.create');
            });
        });

        Route::controller(OptionController::class)->group(function () {
            Route::get('get-attribute', 'listAttributes')->name('admin.noon.get-attribute');
            Route::get('get-image-attribute', 'listImageAttributes')->name('admin.noon.get-image-attribute');
            Route::get('get-gallery-attribute', 'listGalleryAttributes')->name('admin.noon.get-gallery-attribute');
            Route::get('get-noon-credentials', 'listNoonCredential')->name('noon.credential.fetch-all');
            Route::get('get-noon-channel', 'listChannel')->name('noon.channel.fetch-all');
            Route::get('get-noon-currency', 'listCurrency')->name('noon.currency.fetch-all');
            Route::get('get-noon-locale', 'listLocale')->name('noon.locale.fetch-all');
            Route::get('get-noon-attrGroup', 'listAttributeGroup')->name('noon.attribute-group.fetch-all');
            Route::get('get-noon-family', 'listNoonFamily')->name('admin.noon.get-all-family-variants');
        });

    });
});
