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

    /**
     * System Settings hub — extensible, config-driven grouped settings. Lives under
     * the `configuration/` prefix to match its Configuration sidebar section; the
     * explicit routes are declared before the `configuration/{slug?}` wildcard group
     * below so they resolve first. Route names keep the settings.system.* namespace.
     */
    Route::get('configuration/system', [SystemSettingsController::class, 'index'])->name('admin.settings.system.index');

    Route::get('configuration/system/{key}', [SystemSettingsController::class, 'edit'])->name('admin.settings.system.edit');

    Route::put('configuration/system/{key}', [SystemSettingsController::class, 'update'])->name('admin.settings.system.update');

    /**
     * Magic AI settings — the `general.magic_ai` config group surfaced under its own
     * Magic AI section instead of the Configuration hub. Reuses ConfigurationController
     * with fixed slug/slug2 defaults so the stored config codes (general.magic_ai.*)
     * stay unchanged; the shared config form still posts to admin.configuration.store.
     */
    Route::get('magic-ai/settings', [ConfigurationController::class, 'index'])
        ->defaults('slug', 'general')
        ->defaults('slug2', 'magic_ai')
        ->name('admin.magic_ai.settings.index');

    /**
     * Redirect the legacy Configuration deep link to the relocated Magic AI settings
     * page. GET-only and declared before the configuration/{slug?} wildcard so it wins
     * for browser navigation, while the settings form's POST still reaches the wildcard
     * store (admin.configuration.store) untouched.
     */
    Route::get('configuration/general/magic_ai', fn () => redirect()->route('admin.magic_ai.settings.index', [], 301));

    Route::controller(ConfigurationController::class)->prefix('configuration/{slug?}/{slug2?}')->group(function () {

        Route::get('', 'index')->name('admin.configuration.edit');

        Route::post('', 'store')->name('admin.configuration.store');

        Route::get('{path}', 'download')->defaults('_config', [
            'redirect' => 'admin.configuration.index',
        ])->name('admin.configuration.download');
    });
});
