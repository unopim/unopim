<?php

use Illuminate\Support\Facades\Route;
use Webkul\Measurement\Http\Controllers\Api\AttributeMeasurementApiController;
use Webkul\Measurement\Http\Controllers\Api\MeasurementFamilyApiController;
use Webkul\Measurement\Http\Controllers\Api\MeasurementUnitApiController;

Route::group([
    'middleware' => [
        'auth:api',
    ],
], function () {
    /** Measurement API Routes */
    Route::controller(MeasurementFamilyApiController::class)->prefix('measurement')->group(function () {
        Route::get('', 'index')->name('admin.api.measurement.index');
        Route::post('', 'store')->name('admin.api.measurement.store');
        Route::put('{id}', 'update')->name('admin.api.measurement.update');
        Route::delete('{id}', 'destroy')->name('admin.api.measurement.delete');
    });

    // ** Units APIs
    Route::controller(MeasurementUnitApiController::class)->prefix('units')->group(function () {
        Route::get('{familyId}', 'index')->name('admin.api.measurement-units.index');
        Route::post('{familyId}', 'store')->name('admin.api.measurement-units.store');
        Route::put('{familyId}/{code}', 'update')->name('admin.api.measurement-units.update');
        Route::delete('{familyId}/{code}', 'destroy')->name('admin.api.measurement-units.delete');
    });

    Route::controller(AttributeMeasurementApiController::class)->prefix('attribute-measurment')->group(function () {
        Route::get('{familyCode}', 'getUnitsByFamily')->name('admin.api.attribute-measurment.getUnitsByFamily');
        Route::post('{attributeId}', 'store')->name('admin.api.attribute-measurment.store');
        Route::put('{attributeId}', 'update')->name('admin.api.attribute-measurment.store');
    });

});
