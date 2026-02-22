<?php

use Illuminate\Support\Facades\Route;
use Webkul\Order\Http\Controllers\Admin\OrderController;
use Webkul\Order\Http\Controllers\Admin\OrderSyncController;
use Webkul\Order\Http\Controllers\Admin\ProfitabilityController;
use Webkul\Order\Http\Controllers\Admin\WebhookController;

/**
 * Order Admin Routes
 *
 * Routes for order management, synchronization, profitability analysis,
 * and webhook configuration in the admin panel.
 */
Route::group([
    'middleware' => ['web', 'admin_locale'],
    'prefix' => config('app.admin_path'),
], function () {
    Route::prefix('orders')->name('admin.orders.')->group(function () {
        /**
         * Order Management Routes
         */
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{id}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{id}', [OrderController::class, 'destroy'])->name('destroy');

        /**
         * Bulk Operations
         */
        Route::post('/mass-update', [OrderController::class, 'massUpdate'])->name('mass-update');

        /**
         * Export
         */
        Route::get('/export/download', [OrderController::class, 'export'])->name('export');

        /**
         * Order Synchronization Routes
         */
        Route::prefix('sync')->name('sync.')->group(function () {
            Route::get('/', [OrderSyncController::class, 'index'])->name('index');
            Route::get('/{id}', [OrderSyncController::class, 'show'])->name('show');
            Route::post('/channel/{id}', [OrderSyncController::class, 'syncChannel'])->name('channel');
            Route::post('/all', [OrderSyncController::class, 'syncAll'])->name('all');
            Route::post('/{id}/retry', [OrderSyncController::class, 'retry'])->name('retry');
        });

        /**
         * Profitability Analysis Routes
         */
        Route::prefix('profitability')->name('profitability.')->group(function () {
            Route::get('/', [ProfitabilityController::class, 'index'])->name('index');
            Route::get('/by-channel', [ProfitabilityController::class, 'byChannel'])->name('by-channel');
            Route::get('/by-product', [ProfitabilityController::class, 'byProduct'])->name('by-product');
            Route::get('/by-date-range', [ProfitabilityController::class, 'byDateRange'])->name('by-date-range');
            Route::get('/export', [ProfitabilityController::class, 'export'])->name('export');
        });

        /**
         * Webhook Configuration Routes
         */
        Route::prefix('webhooks')->name('webhooks.')->group(function () {
            Route::get('/', [WebhookController::class, 'index'])->name('index');
            Route::get('/create', [WebhookController::class, 'create'])->name('create');
            Route::post('/', [WebhookController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [WebhookController::class, 'edit'])->name('edit');
            Route::put('/{id}', [WebhookController::class, 'update'])->name('update');
            Route::delete('/{id}', [WebhookController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [WebhookController::class, 'toggleStatus'])->name('toggle-status');
        });
    });
});
