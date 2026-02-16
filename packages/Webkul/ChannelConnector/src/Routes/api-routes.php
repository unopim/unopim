<?php

use Illuminate\Support\Facades\Route;
use Webkul\ChannelConnector\Http\Controllers\Api\ConflictApiController;
use Webkul\ChannelConnector\Http\Controllers\Api\ConnectionTestApiController;
use Webkul\ChannelConnector\Http\Controllers\Api\ConnectorApiController;
use Webkul\ChannelConnector\Http\Controllers\Api\MappingApiController;
use Webkul\ChannelConnector\Http\Controllers\Api\SyncApiController;
use Webkul\ChannelConnector\Http\Controllers\Api\SyncJobApiController;
use Webkul\ChannelConnector\Http\Controllers\WebhookController;

Route::group([
    'middleware' => ['api', 'auth:api', 'api.scope', 'accept.json', 'request.locale', 'throttle:60,1'],
    'prefix'     => 'api/v1/rest/channel-connectors',
], function () {
    // Static routes: Connectors list & create
    Route::get('/', [ConnectorApiController::class, 'index'])->name('admin.api.channel_connector.connectors.index');
    Route::post('/', [ConnectorApiController::class, 'store'])->name('admin.api.channel_connector.connectors.store');

    // Sync Dashboard (all connectors) - static paths BEFORE wildcards
    Route::get('/dashboard/sync', [SyncJobApiController::class, 'index'])->name('admin.api.channel_connector.dashboard.index');
    Route::get('/dashboard/sync/{id}', [SyncJobApiController::class, 'show'])->name('admin.api.channel_connector.dashboard.show');
    Route::post('/dashboard/sync/{id}/retry', [SyncJobApiController::class, 'retry'])->name('admin.api.channel_connector.dashboard.retry');

    // Wildcard routes: /{code}/*
    Route::get('/{code}', [ConnectorApiController::class, 'show'])->name('admin.api.channel_connector.connectors.show');
    Route::put('/{code}', [ConnectorApiController::class, 'update'])->name('admin.api.channel_connector.connectors.update');
    Route::delete('/{code}', [ConnectorApiController::class, 'destroy'])->name('admin.api.channel_connector.connectors.destroy');

    // Connection Test
    Route::post('/{code}/test', [ConnectionTestApiController::class, 'test'])->name('admin.api.channel_connector.connectors.test');

    // Field Mappings
    Route::get('/{code}/mappings', [MappingApiController::class, 'index'])->name('admin.api.channel_connector.mappings.index');
    Route::put('/{code}/mappings', [MappingApiController::class, 'store'])->name('admin.api.channel_connector.mappings.store');

    // Sync Jobs
    Route::get('/{code}/sync', [SyncApiController::class, 'index'])->name('admin.api.channel_connector.sync.index');
    Route::post('/{code}/sync', [SyncApiController::class, 'trigger'])->name('admin.api.channel_connector.sync.trigger');
    Route::get('/{code}/sync/{jobId}', [SyncApiController::class, 'show'])->name('admin.api.channel_connector.sync.show');
    Route::post('/{code}/sync/{jobId}/retry', [SyncApiController::class, 'retry'])->name('admin.api.channel_connector.sync.retry');

    // Conflicts
    Route::get('/{code}/conflicts', [ConflictApiController::class, 'index'])->name('admin.api.channel_connector.conflicts.index');
    Route::get('/{code}/conflicts/{id}', [ConflictApiController::class, 'show'])->name('admin.api.channel_connector.conflicts.show');
    Route::put('/{code}/conflicts/{id}/resolve', [ConflictApiController::class, 'resolve'])->name('admin.api.channel_connector.conflicts.resolve');
});

// Webhook endpoint - public, no auth middleware
Route::post('/webhooks/channel-connectors/{token}', [WebhookController::class, 'receive'])
    ->middleware('throttle:30,1')
    ->name('channel_connector.webhooks.receive');
