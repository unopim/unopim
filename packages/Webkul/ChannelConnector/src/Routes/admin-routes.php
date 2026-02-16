<?php

use Illuminate\Support\Facades\Route;
use Webkul\ChannelConnector\Http\Controllers\Admin\ConflictController;
use Webkul\ChannelConnector\Http\Controllers\Admin\ConnectionTestController;
use Webkul\ChannelConnector\Http\Controllers\Admin\ConnectorController;
use Webkul\ChannelConnector\Http\Controllers\Admin\MappingController;
use Webkul\ChannelConnector\Http\Controllers\Admin\SallaOAuthController;
use Webkul\ChannelConnector\Http\Controllers\Admin\SyncController;
use Webkul\ChannelConnector\Http\Controllers\Admin\SyncDashboardController;

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url', 'admin').'/integrations/channel-connectors',
], function () {
    // Static routes: Connectors list & create
    Route::get('/', [ConnectorController::class, 'index'])->name('admin.channel_connector.connectors.index');
    Route::get('/create', [ConnectorController::class, 'create'])->name('admin.channel_connector.connectors.create');
    Route::post('/', [ConnectorController::class, 'store'])->name('admin.channel_connector.connectors.store');

    // Sync Dashboard (across all connectors) - static paths BEFORE wildcards
    Route::get('/dashboard/sync', [SyncDashboardController::class, 'index'])->name('admin.channel_connector.dashboard.index');
    Route::get('/dashboard/sync/{id}', [SyncDashboardController::class, 'show'])->name('admin.channel_connector.dashboard.show');
    Route::post('/dashboard/sync/{id}/retry', [SyncDashboardController::class, 'retry'])->name('admin.channel_connector.dashboard.retry');
    Route::get('/dashboard/sync/{id}/status', [SyncDashboardController::class, 'status'])->name('admin.channel_connector.dashboard.status');

    // Conflicts - static paths BEFORE wildcards
    Route::get('/conflicts', [ConflictController::class, 'index'])->name('admin.channel_connector.conflicts.index');
    Route::get('/conflicts/{id}', [ConflictController::class, 'show'])->name('admin.channel_connector.conflicts.show');
    Route::put('/conflicts/{id}/resolve', [ConflictController::class, 'resolve'])->name('admin.channel_connector.conflicts.resolve');

    // Salla OAuth routes
    Route::get('/{code}/salla/redirect', [SallaOAuthController::class, 'redirect'])
        ->name('admin.channel_connector.salla.redirect');
    Route::get('/{code}/salla/callback', [SallaOAuthController::class, 'callback'])
        ->name('admin.channel_connector.salla.callback');

    // Wildcard routes: /{code}/*
    Route::get('/{code}/edit', [ConnectorController::class, 'edit'])->name('admin.channel_connector.connectors.edit');
    Route::put('/{code}', [ConnectorController::class, 'update'])->name('admin.channel_connector.connectors.update');
    Route::delete('/{code}', [ConnectorController::class, 'destroy'])->name('admin.channel_connector.connectors.destroy');

    // Connection Test
    Route::post('/{code}/test', [ConnectionTestController::class, 'test'])->name('admin.channel_connector.connectors.test');

    // Field Mappings
    Route::get('/{code}/mappings', [MappingController::class, 'index'])->name('admin.channel_connector.mappings.index');
    Route::put('/{code}/mappings', [MappingController::class, 'store'])->name('admin.channel_connector.mappings.store');
    Route::get('/{code}/mappings/preview', [MappingController::class, 'preview'])->name('admin.channel_connector.mappings.preview');

    // Sync
    Route::get('/{code}/sync', [SyncController::class, 'index'])->name('admin.channel_connector.sync.index');
    Route::post('/{code}/sync/preview', [SyncController::class, 'preview'])->name('admin.channel_connector.sync.preview');
    Route::post('/{code}/sync', [SyncController::class, 'trigger'])->name('admin.channel_connector.sync.trigger');
    Route::get('/{code}/sync/{jobId}', [SyncController::class, 'show'])->name('admin.channel_connector.sync.show');

    // Webhooks
    Route::get('/{code}/webhooks', [ConnectorController::class, 'webhooks'])->name('admin.channel_connector.webhooks.index');
    Route::post('/{code}/webhooks/manage', [ConnectorController::class, 'manageWebhooks'])->name('admin.channel_connector.webhooks.manage');
});
