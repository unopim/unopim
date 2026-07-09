<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\ConfigurationController;
use Webkul\Admin\Http\Controllers\SystemController;
use Webkul\Admin\Http\Controllers\SystemSettingsController;

/**
 * Configuration routes.
 */
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::get('configuration/search', [ConfigurationController::class, 'search'])->name('admin.configuration.search');

    Route::get('configuration/system-information', [SystemController::class, 'information'])->name('admin.configuration.system.information');

    Route::get('configuration/system-settings', [SystemController::class, 'settings'])->name('admin.configuration.system.settings');

    /**
     * System Settings hub — extensible, config-driven grouped settings.
     */
    Route::get('settings/system', [SystemSettingsController::class, 'index'])->name('admin.settings.system.index');

    Route::get('settings/system/{key}', [SystemSettingsController::class, 'edit'])->name('admin.settings.system.edit');

    Route::put('settings/system/{key}', [SystemSettingsController::class, 'update'])->name('admin.settings.system.update');

    Route::controller(ConfigurationController::class)->prefix('configuration/{slug?}/{slug2?}')->group(function () {

        Route::get('', 'index')->name('admin.configuration.edit');

        Route::post('', 'store')->name('admin.configuration.store');

        Route::get('{path}', 'download')->defaults('_config', [
            'redirect' => 'admin.configuration.index',
        ])->name('admin.configuration.download');
    });
});
