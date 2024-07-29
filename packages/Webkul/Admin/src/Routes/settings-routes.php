<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Settings\ChannelController;
use Webkul\Admin\Http\Controllers\Settings\CurrencyController;
use Webkul\Admin\Http\Controllers\Settings\DataTransfer\ExportController;
use Webkul\Admin\Http\Controllers\Settings\DataTransfer\ImportController;
use Webkul\Admin\Http\Controllers\Settings\DataTransfer\TrackerController;
use Webkul\Admin\Http\Controllers\Settings\LocaleController;
use Webkul\Admin\Http\Controllers\Settings\RoleController;
use Webkul\Admin\Http\Controllers\Settings\UserController;

/**
 * Settings routes.
 */
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('settings')->group(function () {
        /**
         * Channels routes.
         */
        Route::controller(ChannelController::class)->prefix('channels')->group(function () {
            Route::get('', 'index')->name('admin.settings.channels.index');

            Route::get('create', 'create')->name('admin.settings.channels.create');

            Route::post('create', 'store')->name('admin.settings.channels.store');

            Route::get('edit/{id}', 'edit')->name('admin.settings.channels.edit');

            Route::put('edit/{id}', 'update')->name('admin.settings.channels.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.settings.channels.delete');
        });

        /**
         * Currencies routes.
         */
        Route::controller(CurrencyController::class)->prefix('currencies')->group(function () {
            Route::get('', 'index')->name('admin.settings.currencies.index');

            Route::post('create', 'store')->name('admin.settings.currencies.store');

            Route::get('edit/{id}', 'edit')->name('admin.settings.currencies.edit');

            Route::put('edit', 'update')->name('admin.settings.currencies.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.settings.currencies.delete');

            Route::post('mass-edit', 'massUpdate')->name('admin.settings.currencies.mass_update');

            Route::post('mass-delete', 'massDestroy')->name('admin.settings.currencies.mass_delete');
        });

        /**
         * Locales routes.
         */
        Route::controller(LocaleController::class)->prefix('locales')->group(function () {
            Route::get('', 'index')->name('admin.settings.locales.index');

            Route::post('create', 'store')->name('admin.settings.locales.store');

            Route::get('edit/{id}', 'edit')->name('admin.settings.locales.edit');

            Route::put('edit', 'update')->name('admin.settings.locales.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.settings.locales.delete');

            Route::post('mass-edit', 'massUpdate')->name('admin.settings.locales.mass_update');

            Route::post('mass-delete', 'massDestroy')->name('admin.settings.locales.mass_delete');
        });

        /**
         * Roles routes.
         */
        Route::controller(RoleController::class)->prefix('roles')->group(function () {
            Route::get('', 'index')->name('admin.settings.roles.index');

            Route::get('create', 'create')->name('admin.settings.roles.create');

            Route::post('create', 'store')->name('admin.settings.roles.store');

            Route::get('edit/{id}', 'edit')->name('admin.settings.roles.edit');

            Route::put('edit/{id}', 'update')->name('admin.settings.roles.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.settings.roles.delete');
        });

        /**
         * Users routes.
         */
        Route::controller(UserController::class)->prefix('users')->group(function () {
            Route::get('', 'index')->name('admin.settings.users.index');

            Route::post('create', 'store')->name('admin.settings.users.store');

            Route::get('edit/{id}', 'edit')->name('admin.settings.users.edit');

            Route::put('edit', 'update')->name('admin.settings.users.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.settings.users.delete');

            Route::put('confirm', 'destroySelf')->name('admin.settings.users.destroy');
        });

        /**
         * Data Transfer routes.
         */
        Route::prefix('data-transfer')->group(function () {

            /**
             * Sync Tracker routes.
             */
            Route::controller(TrackerController::class)->prefix('tracker')->group(function () {
                Route::get('', 'index')->name('admin.settings.data_transfer.tracker.index');
                Route::get('track/{batch_id}', 'view')->name('admin.settings.data_transfer.tracker.view');
                Route::get('track/download/{batch_id}', 'download')->name('admin.settings.data_transfer.tracker.download');
                Route::get('track/archive/download/{batch_id}', 'downloadArchive')->name('admin.settings.data_transfer.tracker.archive.download');
            });
            /**
             * Import routes.
             */
            Route::controller(ImportController::class)->prefix('imports')->group(function () {
                Route::get('', 'index')->name('admin.settings.data_transfer.imports.index');

                Route::get('create', 'create')->name('admin.settings.data_transfer.imports.create');

                Route::post('create', 'store')->name('admin.settings.data_transfer.imports.store');

                Route::get('edit/{id}', 'edit')->name('admin.settings.data_transfer.imports.edit');

                Route::put('edit/{id}', 'update')->name('admin.settings.data_transfer.imports.update');

                Route::delete('destroy/{id}', 'destroy')->name('admin.settings.data_transfer.imports.delete');

                Route::get('import/{id}', 'importView')->name('admin.settings.data_transfer.imports.import-view');

                Route::get('validate/{id}', 'validateImport')->name('admin.settings.data_transfer.imports.validate');

                Route::put('import-now/{id}', 'importNow')->name('admin.settings.data_transfer.imports.import_now');

                Route::get('start/{id}', 'start')->name('admin.settings.data_transfer.imports.start');

                Route::get('link/{id}', 'link')->name('admin.settings.data_transfer.imports.link');

                Route::get('index/{id}', 'indexData')->name('admin.settings.data_transfer.imports.index_data');

                Route::get('stats/{id}/{state?}', 'stats')->name('admin.settings.data_transfer.imports.stats');

                Route::get('download-sample/{type?}', 'downloadSample')->name('admin.settings.data_transfer.imports.download_sample');

                Route::get('download/{id}', 'download')->name('admin.settings.data_transfer.imports.download');

                Route::get('download-error-report/{id}', 'downloadErrorReport')->name('admin.settings.data_transfer.imports.download_error_report');

                Route::get('download-sample-images-zip/{type?}', 'downloadSampleImagesZip')->name('admin.settings.data_transfer.imports.download_sample_zip');
            });

            /**
             * Export routes. admin.settings.data_transfer.exports.export-view
             */
            Route::controller(ExportController::class)->prefix('exports')->group(function () {
                Route::get('', 'index')->name('admin.settings.data_transfer.exports.index');

                Route::get('create', 'create')->name('admin.settings.data_transfer.exports.create');

                Route::post('create', 'store')->name('admin.settings.data_transfer.exports.store');

                Route::get('edit/{id}', 'edit')->name('admin.settings.data_transfer.exports.edit');

                Route::put('edit/{id}', 'update')->name('admin.settings.data_transfer.exports.update');

                Route::delete('destroy/{id}', 'destroy')->name('admin.settings.data_transfer.exports.delete');

                Route::get('export/{id}', 'exportView')->name('admin.settings.data_transfer.exports.export-view');

                Route::get('validate/{id}', 'validateExport')->name('admin.settings.data_transfer.exports.validate');

                Route::put('export-now/{id}', 'exportNow')->name('admin.settings.data_transfer.exports.export_now');

                Route::get('start/{id}', 'start')->name('admin.settings.data_transfer.exports.start');

                Route::get('link/{id}', 'link')->name('admin.settings.data_transfer.exports.link');

                Route::get('index/{id}', 'indexData')->name('admin.settings.data_transfer.exports.index_data');

                Route::get('stats/{id}/{state?}', 'stats')->name('admin.settings.data_transfer.exports.stats');

                Route::get('download-sample/{type?}', 'downloadSample')->name('admin.settings.data_transfer.exports.download_sample');

                Route::get('download-sample-images-zip/{type?}', 'downloadSampleImagesZip')->name('admin.settings.data_transfer.exports.download_sample_zip');

                Route::get('download/{id}', 'download')->name('admin.settings.data_transfer.exports.download');

                Route::get('download-error-report/{id}', 'downloadErrorReport')->name('admin.settings.data_transfer.exports.download_error_report');
            });
        });
    });
});
