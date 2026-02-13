<?php

use Illuminate\Support\Facades\Route;
use Webkul\Completeness\Http\Controllers\CompletenessController;
use Webkul\Completeness\Http\Controllers\CompletenessSettingsController;

Route::group(['middleware' => ['web', 'admin', 'tenant'], 'prefix' => config('app.admin_url')], function () {
    Route::controller(CompletenessSettingsController::class)->prefix('completeness-settings')->group(function () {
        Route::get('{family_id}/edit', 'edit')->name('admin.catalog.families.completeness.edit');

        Route::post('update', 'update')->name('admin.catalog.families.completeness.update');

        Route::post('mass-update', 'massUpdate')->name('admin.catalog.families.completeness.mass_update');
    });

    Route::controller(CompletenessController::class)->prefix('completeness')->group(function () {
        Route::get('dashboard/channel-stats', 'getCompletenessData')->name('admin.dashboard.completeness.data');
    });
});
