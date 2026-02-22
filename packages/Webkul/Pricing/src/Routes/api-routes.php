<?php

use Illuminate\Support\Facades\Route;
use Webkul\Pricing\Http\Controllers\Api\BreakEvenApiController;
use Webkul\Pricing\Http\Controllers\Api\MarginApiController;
use Webkul\Pricing\Http\Controllers\Api\ProductCostApiController;
use Webkul\Pricing\Http\Controllers\Api\RecommendationApiController;
use Webkul\Pricing\Http\Controllers\Api\StrategyApiController;

Route::group([
    'middleware' => ['api', 'auth:api', 'api.scope', 'accept.json', 'request.locale', 'throttle:60,1'],
    'prefix'     => 'api/v1/rest',
], function () {
    // Product Costs
    Route::get('/products/{code}/costs', [ProductCostApiController::class, 'index'])->name('admin.api.pricing.costs.index');
    Route::post('/products/{code}/costs', [ProductCostApiController::class, 'store'])->name('admin.api.pricing.costs.store');
    Route::put('/products/{code}/costs/{costId}', [ProductCostApiController::class, 'update'])->name('admin.api.pricing.costs.update');

    // Break-Even Analysis
    Route::get('/products/{code}/break-even', [BreakEvenApiController::class, 'show'])->name('admin.api.pricing.break-even.show');

    // Price Recommendations
    Route::post('/products/{code}/recommended-price', [RecommendationApiController::class, 'recommend'])->name('admin.api.pricing.recommendations.recommend');

    // Margin Protection Events
    Route::get('/pricing/margin-events', [MarginApiController::class, 'index'])->name('admin.api.pricing.margins.index');
    Route::post('/pricing/margin-events/{id}/approve', [MarginApiController::class, 'approve'])->name('admin.api.pricing.margins.approve');

    // Pricing Strategies
    Route::get('/pricing/strategies', [StrategyApiController::class, 'index'])->name('admin.api.pricing.strategies.index');
    Route::post('/pricing/strategies', [StrategyApiController::class, 'store'])->name('admin.api.pricing.strategies.store');
});
