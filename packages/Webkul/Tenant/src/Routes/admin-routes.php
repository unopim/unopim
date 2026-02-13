<?php

use Illuminate\Support\Facades\Route;
use Webkul\Tenant\Http\Controllers\TenantController;

Route::group([
    'middleware' => ['web', 'admin', 'platform.operator'],
    'prefix'     => config('app.admin_url').'/settings/tenants',
], function () {
    Route::controller(TenantController::class)->group(function () {
        Route::get('/', 'index')->name('admin.settings.tenants.index');
        Route::get('/create', 'create')->name('admin.settings.tenants.create');
        Route::post('/', 'store')->name('admin.settings.tenants.store');
        Route::post('/switch-context', 'switchContext')->name('admin.settings.tenants.switch-context');
        Route::get('/{id}', 'show')->name('admin.settings.tenants.show');
        Route::get('/{id}/edit', 'edit')->name('admin.settings.tenants.edit');
        Route::put('/{id}', 'update')->name('admin.settings.tenants.update');
        Route::delete('/{id}', 'destroy')->name('admin.settings.tenants.destroy');
        Route::post('/{id}/suspend', 'suspend')->name('admin.settings.tenants.suspend');
        Route::post('/{id}/activate', 'activate')->name('admin.settings.tenants.activate');
    });
});
