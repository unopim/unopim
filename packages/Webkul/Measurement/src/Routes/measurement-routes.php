<?php

use Illuminate\Support\Facades\Route;
use Webkul\Measurement\Http\Controllers\Api\AttributeMeasurementApiController;
use Webkul\Measurement\Http\Controllers\Api\MeasurementFamilyApiController;
use Webkul\Measurement\Http\Controllers\Api\MeasurementUnitApiController;

Route::group([
    'middleware' => [
        'auth:api',
    ],
], function (): void {
    Route::controller(MeasurementFamilyApiController::class)->prefix('measurement')->group(function (): void {
        Route::get('', 'index')->name('admin.api.measurement.index');
        Route::get('{code}', 'show')->name('admin.api.measurement.show');
        Route::post('', 'store')->name('admin.api.measurement.store');
        Route::put('{code}', 'update')->name('admin.api.measurement.update');
        Route::delete('{code}', 'destroy')->name('admin.api.measurement.delete');
    });

    Route::controller(MeasurementUnitApiController::class)->prefix('units')->group(function (): void {
        Route::get('{familyCode}', 'index')->name('admin.api.measurement-units.index');
        Route::get('{familyCode}/{code}', 'show')->name('admin.api.measurement-units.show');
        Route::post('{familyCode}', 'store')->name('admin.api.measurement-units.store');
        Route::put('{familyCode}/{code}', 'update')->name('admin.api.measurement-units.update');
        Route::delete('{familyCode}/{code}', 'destroy')->name('admin.api.measurement-units.delete');
    });

    Route::controller(AttributeMeasurementApiController::class)->prefix('attribute-measurement')->group(function (): void {
        Route::get('config/{attributeCode}', 'show')->name('admin.api.attribute-measurement.show');
        Route::get('{familyCode}', 'getUnitsByFamily')->name('admin.api.attribute-measurement.getUnitsByFamily');
        Route::post('{attributeCode}', 'store')->name('admin.api.attribute-measurement.store');
        Route::put('{attributeCode}', 'update')->name('admin.api.attribute-measurement.update');
    });

});
