<?php

use Illuminate\Support\Facades\Route;
use Webkul\Tenant\Http\Controllers\API\TenantApiController;

Route::group([
    'middleware' => ['auth:api', 'tenant.token', 'tenant.safe-errors'],
    'prefix'     => 'api/v1/tenants',
], function () {
    Route::controller(TenantApiController::class)->group(function () {
        Route::get('/', 'index')->name('api.v1.tenants.index');
        Route::get('/{id}', 'show')->name('api.v1.tenants.show');
        Route::post('/', 'store')->name('api.v1.tenants.store');
        Route::put('/{id}', 'update')->name('api.v1.tenants.update');
        Route::delete('/{id}', 'destroy')->name('api.v1.tenants.destroy');
        Route::post('/{id}/suspend', 'suspend')->name('api.v1.tenants.suspend');
        Route::post('/{id}/activate', 'activate')->name('api.v1.tenants.activate');
    });
});
