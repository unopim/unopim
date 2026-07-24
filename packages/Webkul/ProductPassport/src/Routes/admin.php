<?php

use Illuminate\Support\Facades\Route;
use Webkul\ProductPassport\Http\Controllers\PassportMappingController;
use Webkul\ProductPassport\Http\Controllers\ProductPassportController;
use Webkul\ProductPassport\Http\Controllers\PublicationController;

Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function (): void {
    Route::controller(PublicationController::class)->prefix('catalog/passports')->group(function (): void {
        Route::get('', 'index')->name('admin.catalog.passports.index');
        Route::post('publish/{product}', 'publish')->name('admin.catalog.passports.publish');
        Route::post('mass-publish', 'massPublish')->name('admin.catalog.passports.mass_publish');
        Route::post('bulk-publish', 'bulkPublish')->name('admin.catalog.passports.bulk-publish');
        Route::post('withdraw/{publication}', 'withdraw')->name('admin.catalog.passports.withdraw');
    });

    Route::controller(PassportMappingController::class)->prefix('catalog/passports')->group(function (): void {
        Route::get('mapping', 'edit')->name('admin.catalog.passports.mapping.edit');
        Route::put('mapping', 'update')->name('admin.catalog.passports.mapping.update');
    });

    Route::get('products/{product}/passport', [ProductPassportController::class, 'show'])
        ->name('admin.catalog.products.passport.show');
});
