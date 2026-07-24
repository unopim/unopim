<?php

use Illuminate\Support\Facades\Route;
use Webkul\AdminApi\Http\Controllers\API\Catalog\PassportController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::controller(PassportController::class)->prefix('passports')->group(function () {
        Route::get('', 'index')->name('admin.api.passports.index');
        Route::get('mapping', 'mapping')->name('admin.api.passports.mapping');
        Route::get('{sku}', 'get')->name('admin.api.passports.get');
        Route::post('publish/{sku}', 'publish')->name('admin.api.passports.publish');
        Route::post('withdraw/{id}', 'withdraw')->whereNumber('id')->name('admin.api.passports.withdraw');
    });
});
