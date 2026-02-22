<?php

use Illuminate\Support\Facades\Route;
use Webkul\Order\Http\Controllers\Api\V1\OrderController;
use Webkul\Order\Http\Controllers\Api\V1\ProfitabilityController;
use Webkul\Order\Http\Controllers\Api\V1\WebhookReceiverController;

/**
 * Order API Routes
 *
 * RESTful API routes for order management, webhook reception,
 * and profitability analysis with OAuth2 authentication.
 */
Route::group([
    'middleware' => ['auth:api'],
    'prefix' => 'api/v1',
], function () {
    /**
     * Authenticated Order API Routes
     */
    Route::prefix('orders')->name('api.v1.orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])->name('update-status');

        /**
         * Profitability API Routes
         */
        Route::prefix('profitability')->name('profitability.')->group(function () {
            Route::get('/{orderId}', [ProfitabilityController::class, 'calculate'])->name('calculate');
            Route::get('/channel/{channelId}', [ProfitabilityController::class, 'aggregateByChannel'])->name('aggregate-by-channel');
        });
    });
});

/**
 * Webhook Receiver Routes (Public - No Authentication Required)
 *
 * These routes are publicly accessible for external services to send webhooks.
 * Authentication is handled via HMAC signature verification.
 */
Route::group([
    'prefix' => 'api/v1/webhooks',
    'middleware' => ['throttle:60,1'], // Rate limit: 60 requests per minute
], function () {
    /**
     * Generic Webhook Receiver
     */
    Route::post('/{channelCode}', [WebhookReceiverController::class, 'receive'])
        ->name('api.v1.webhooks.receive');

    /**
     * Platform-Specific Webhook Receivers
     */
    Route::post('/salla/events', [WebhookReceiverController::class, 'salla'])
        ->name('api.v1.webhooks.salla')
        ->middleware('throttle:120,1'); // Higher limit for Salla

    Route::post('/shopify/events', [WebhookReceiverController::class, 'shopify'])
        ->name('api.v1.webhooks.shopify')
        ->middleware('throttle:120,1'); // Higher limit for Shopify

    Route::post('/woocommerce/events', [WebhookReceiverController::class, 'woocommerce'])
        ->name('api.v1.webhooks.woocommerce')
        ->middleware('throttle:120,1'); // Higher limit for WooCommerce
});
