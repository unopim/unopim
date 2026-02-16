<?php

use Illuminate\Support\Facades\Route;
use Webkul\Magento2\Http\Controllers\CredentialController;
use Webkul\Magento2\Http\Controllers\ImportMappingController;
use Webkul\Magento2\Http\Controllers\MappingController;
use Webkul\Magento2\Http\Controllers\OptionController;
use Webkul\Magento2\Http\Controllers\SettingController;

/**
 * Magento2 routes.
 */
Route::group(['middleware' => ['web', 'admin', 'tenant'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('magento2')->group(function () {

        Route::controller(CredentialController::class)->prefix('credentials')->group(function () {
            Route::get('', 'index')->name('magento2.credentials.index');
            Route::post('create', 'store')->name('magento2.credentials.store');
            Route::get('edit/{id}', 'edit')->name('magento2.credentials.edit');
            Route::put('update/{id}', 'update')->name('magento2.credentials.update');
            Route::delete('delete/{id}', 'destroy')->name('magento2.credentials.delete');
        });

        Route::prefix('export')->group(function () {
            Route::controller(SettingController::class)->prefix('settings')->group(function () {
                Route::get('{id}', 'index')->name('admin.magento2.settings');
                Route::post('create', 'store')->name('magento2.export-settings.create');
            });
            Route::controller(MappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.magento2.export-mappings');
                Route::post('create', 'store')->name('magento2.export-mappings.create');
            });
        });

        Route::prefix('import')->group(function () {
            Route::controller(ImportMappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.magento2.import-mappings');
                Route::post('create', 'store')->name('magento2.import-mappings.create');
            });
        });

        Route::controller(OptionController::class)->group(function () {
            Route::get('get-attribute', 'listAttributes')->name('admin.magento2.get-attribute');
            Route::get('get-image-attribute', 'listImageAttributes')->name('admin.magento2.get-image-attribute');
            Route::get('get-gallery-attribute', 'listGalleryAttributes')->name('admin.magento2.get-gallery-attribute');
            Route::get('get-magento2-credentials', 'listMagento2Credential')->name('magento2.credential.fetch-all');
            Route::get('get-magento2-channel', 'listChannel')->name('magento2.channel.fetch-all');
            Route::get('get-magento2-currency', 'listCurrency')->name('magento2.currency.fetch-all');
            Route::get('get-magento2-locale', 'listLocale')->name('magento2.locale.fetch-all');
            Route::get('get-magento2-attrGroup', 'listAttributeGroup')->name('magento2.attribute-group.fetch-all');
            Route::get('get-magento2-family', 'listMagento2Family')->name('admin.magento2.get-all-family-variants');
        });

    });
});
