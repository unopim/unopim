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

    Route::get('configuration/system', [SystemSettingsController::class, 'index'])->name('admin.settings.system.index');

    Route::get('configuration/system/{key}', [SystemSettingsController::class, 'edit'])->name('admin.settings.system.edit');

    Route::put('configuration/system/{key}', [SystemSettingsController::class, 'update'])->name('admin.settings.system.update');

    Route::get('magic-ai/settings', [ConfigurationController::class, 'index'])
        ->defaults('slug', 'general')
        ->defaults('slug2', 'magic_ai')
        ->name('admin.magic_ai.settings.index');

    Route::post('magic-ai/settings', [ConfigurationController::class, 'store'])
        ->defaults('slug', 'general')
        ->defaults('slug2', 'magic_ai')
        ->name('admin.magic_ai.settings.store');

    Route::get('configuration/general/magic_ai', fn () => redirect()->route('admin.magic_ai.settings.index', [], 301));

    Route::controller(ConfigurationController::class)
        ->prefix('configuration/{slug?}/{slug2?}')
        ->where(['slug' => '(?!(?:webhook|integrations)(?:/|$))[^/]+'])
        ->group(function () {
            Route::get('', 'index')->name('admin.configuration.edit');

            Route::post('', 'store')->name('admin.configuration.store');

            Route::get('{path}', 'download')->defaults('_config', [
                'redirect' => 'admin.configuration.edit',
            ])->name('admin.configuration.download');
        });
});
