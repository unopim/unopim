<?php

use Illuminate\Support\Facades\Route;
use Webkul\Ebay\Http\Controllers\CredentialController;
use Webkul\Ebay\Http\Controllers\ImportMappingController;
use Webkul\Ebay\Http\Controllers\MappingController;
use Webkul\Ebay\Http\Controllers\OptionController;
use Webkul\Ebay\Http\Controllers\SettingController;

/**
 * Ebay routes.
 */
Route::group(['middleware' => ['web', 'admin', 'tenant'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('ebay')->group(function () {

        Route::controller(CredentialController::class)->prefix('credentials')->group(function () {
            Route::get('', 'index')->name('ebay.credentials.index');
            Route::post('create', 'store')->name('ebay.credentials.store');
            Route::get('edit/{id}', 'edit')->name('ebay.credentials.edit');
            Route::put('update/{id}', 'update')->name('ebay.credentials.update');
            Route::delete('delete/{id}', 'destroy')->name('ebay.credentials.delete');
        });

        Route::prefix('export')->group(function () {
            Route::controller(SettingController::class)->prefix('settings')->group(function () {
                Route::get('{id}', 'index')->name('admin.ebay.settings');
                Route::post('create', 'store')->name('ebay.export-settings.create');
            });
            Route::controller(MappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.ebay.export-mappings');
                Route::post('create', 'store')->name('ebay.export-mappings.create');
            });
        });

        Route::prefix('import')->group(function () {
            Route::controller(ImportMappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.ebay.import-mappings');
                Route::post('create', 'store')->name('ebay.import-mappings.create');
            });
        });

        Route::controller(OptionController::class)->group(function () {
            Route::get('get-attribute', 'listAttributes')->name('admin.ebay.get-attribute');
            Route::get('get-image-attribute', 'listImageAttributes')->name('admin.ebay.get-image-attribute');
            Route::get('get-gallery-attribute', 'listGalleryAttributes')->name('admin.ebay.get-gallery-attribute');
            Route::get('get-ebay-credentials', 'listEbayCredential')->name('ebay.credential.fetch-all');
            Route::get('get-ebay-channel', 'listChannel')->name('ebay.channel.fetch-all');
            Route::get('get-ebay-currency', 'listCurrency')->name('ebay.currency.fetch-all');
            Route::get('get-ebay-locale', 'listLocale')->name('ebay.locale.fetch-all');
            Route::get('get-ebay-attrGroup', 'listAttributeGroup')->name('ebay.attribute-group.fetch-all');
            Route::get('get-ebay-family', 'listEbayFamily')->name('admin.ebay.get-all-family-variants');
        });

    });
});
