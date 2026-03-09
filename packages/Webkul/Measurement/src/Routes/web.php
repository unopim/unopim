<?php

use Illuminate\Support\Facades\Route;
use Webkul\Measurement\Http\Controllers\AttributeController;
use Webkul\Measurement\Http\Controllers\MeasurementFamilyController;
use Webkul\Measurement\Http\Controllers\MeasurementUnitsController;
use Webkul\Measurement\Http\Controllers\MeasurementOptionsController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => 'admin/measurement'], function () {

    // Measurement Families
        Route::controller(MeasurementFamilyController::class)->prefix('families')->group(function () {
            Route::get('/', 'index')->name('admin.measurement.families.index');
            Route::get('/create', 'create')->name('admin.measurement.families.create');
            Route::post('/', 'store')->name('admin.measurement.families.store');
            Route::get('/{id}/edit', 'edit')->name('admin.measurement.families.edit');
            Route::put('/{id}', 'update')->name('admin.measurement.families.update');
            Route::delete('/{id}', 'destroy')->name('admin.measurement.families.delete');
            Route::post('/mass-delete', 'massDelete')->name('admin.measurement.families.mass_delete');
        });

         Route::controller(MeasurementUnitsController::class)->prefix('units')->group(function () {
            Route::get('measurement-families/{id}/units', 'units')->name('admin.measurement.families.units');
            Route::post('{id}/units', 'storeUnit')->name('admin.measurement.families.units.store');
            Route::get('measurement-families/{familyId}/units/{code}/edit', 'editUnit')->name('admin.measurement.families.units.edit');
            Route::put('measurement-families/{familyId}/units/{code}/update', 'updateUnit')->name('admin.measurement.families.units.update');
            Route::delete('measurement-families/{familyId}/units/{code}', 'deleteUnit')->name('admin.measurement.families.units.delete');
            Route::post('/unitmass-delete', 'unitmassDelete')->name('admin.measurement.families.unitmass_delete');
        });



Route::get('/measurement/attribute/{attributeId}', [AttributeController::class, 'getAttributeMeasurement'])->name('measurement.attribute');
Route::get('attribute-units', [MeasurementOptionsController::class, 'getOptions'])->name('admin.measurement.attribute.units');

});
