<?php

use Illuminate\Support\Facades\Route;
use Webkul\Amazon\Http\Controllers\CredentialController;
use Webkul\Amazon\Http\Controllers\ImportMappingController;
use Webkul\Amazon\Http\Controllers\MappingController;
use Webkul\Amazon\Http\Controllers\OptionController;
use Webkul\Amazon\Http\Controllers\SettingController;

/**
 * Amazon routes.
 */
Route::group(['middleware' => ['web', 'admin', 'tenant'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('amazon')->group(function () {

        Route::controller(CredentialController::class)->prefix('credentials')->group(function () {
            Route::get('', 'index')->name('amazon.credentials.index');
            Route::post('create', 'store')->name('amazon.credentials.store');
            Route::get('edit/{id}', 'edit')->name('amazon.credentials.edit');
            Route::put('update/{id}', 'update')->name('amazon.credentials.update');
            Route::delete('delete/{id}', 'destroy')->name('amazon.credentials.delete');
        });

        Route::prefix('export')->group(function () {
            Route::controller(SettingController::class)->prefix('settings')->group(function () {
                Route::get('{id}', 'index')->name('admin.amazon.settings');
                Route::post('create', 'store')->name('amazon.export-settings.create');
            });
            Route::controller(MappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.amazon.export-mappings');
                Route::post('create', 'store')->name('amazon.export-mappings.create');
            });
        });

        Route::prefix('import')->group(function () {
            Route::controller(ImportMappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.amazon.import-mappings');
                Route::post('create', 'store')->name('amazon.import-mappings.create');
            });
        });

        Route::controller(OptionController::class)->group(function () {
            Route::get('get-attribute', 'listAttributes')->name('admin.amazon.get-attribute');
            Route::get('get-image-attribute', 'listImageAttributes')->name('admin.amazon.get-image-attribute');
            Route::get('get-gallery-attribute', 'listGalleryAttributes')->name('admin.amazon.get-gallery-attribute');
            Route::get('get-amazon-credentials', 'listAmazonCredential')->name('amazon.credential.fetch-all');
            Route::get('get-amazon-channel', 'listChannel')->name('amazon.channel.fetch-all');
            Route::get('get-amazon-currency', 'listCurrency')->name('amazon.currency.fetch-all');
            Route::get('get-amazon-locale', 'listLocale')->name('amazon.locale.fetch-all');
            Route::get('get-amazon-attrGroup', 'listAttributeGroup')->name('amazon.attribute-group.fetch-all');
            Route::get('get-amazon-family', 'listAmazonFamily')->name('admin.amazon.get-all-family-variants');
        });

    });
});
