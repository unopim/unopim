<?php

use Illuminate\Support\Facades\Route;
use Webkul\ProductPassport\Http\Controllers\PassportMappingController;
use Webkul\ProductPassport\Http\Controllers\PassportTemplateController;
use Webkul\ProductPassport\Http\Controllers\ProductPassportController;
use Webkul\ProductPassport\Http\Controllers\PublicationController;

Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function (): void {
    Route::controller(PublicationController::class)->prefix('catalog/passports')->group(function (): void {
        Route::get('', 'index')->name('admin.catalog.passports.index');
        Route::post('publish/{product}', 'publish')->name('admin.catalog.passports.publish');
        Route::post('mass-publish', 'massPublish')->name('admin.catalog.passports.mass_publish');
        Route::post('bulk-publish', 'bulkPublish')->name('admin.catalog.passports.bulk-publish');
        Route::post('withdraw/{publication}', 'withdraw')->name('admin.catalog.passports.withdraw');
        Route::get('{publication}/versions', 'versions')->name('admin.catalog.passports.versions');
        Route::post('{publication}/versions/republish', 'republish')->name('admin.catalog.passports.republish');
    });

    Route::controller(PassportMappingController::class)->prefix('catalog/passports')->group(function (): void {
        Route::get('mapping', 'edit')->name('admin.catalog.passports.mapping.edit');
        Route::put('mapping', 'update')->name('admin.catalog.passports.mapping.update');
    });

    Route::controller(PassportTemplateController::class)->prefix('catalog/passports/templates')->group(function (): void {
        Route::get('', 'index')->name('admin.catalog.passports.templates.index');
        Route::post('', 'store')->name('admin.catalog.passports.templates.store');
        Route::get('{id}/edit', 'edit')->whereNumber('id')->name('admin.catalog.passports.templates.edit');
        Route::put('{id}', 'update')->whereNumber('id')->name('admin.catalog.passports.templates.update');
        Route::delete('{id}', 'destroy')->whereNumber('id')->name('admin.catalog.passports.templates.delete');
    });

    Route::get('products/{product}/passport', [ProductPassportController::class, 'show'])
        ->name('admin.catalog.products.passport.show');

    Route::get('products/{product}/passport/preview', [ProductPassportController::class, 'preview'])
        ->name('admin.catalog.products.passport.preview');
});
