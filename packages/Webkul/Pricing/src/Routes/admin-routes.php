<?php

use Illuminate\Support\Facades\Route;
use Webkul\Pricing\Http\Controllers\Admin\BreakEvenController;
use Webkul\Pricing\Http\Controllers\Admin\ChannelCostController;
use Webkul\Pricing\Http\Controllers\Admin\CostController;
use Webkul\Pricing\Http\Controllers\Admin\MarginController;
use Webkul\Pricing\Http\Controllers\Admin\RecommendationController;
use Webkul\Pricing\Http\Controllers\Admin\StrategyController;

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url', 'admin').'/pricing',
], function () {
    // Product Costs
    Route::get('/costs', [CostController::class, 'index'])->name('admin.pricing.costs.index');
    Route::get('/costs/create', [CostController::class, 'create'])->name('admin.pricing.costs.create');
    Route::post('/costs', [CostController::class, 'store'])->name('admin.pricing.costs.store');
    Route::get('/costs/{id}/edit', [CostController::class, 'edit'])->name('admin.pricing.costs.edit');
    Route::put('/costs/{id}', [CostController::class, 'update'])->name('admin.pricing.costs.update');
    Route::delete('/costs/{id}', [CostController::class, 'destroy'])->name('admin.pricing.costs.destroy');
    Route::get('/costs/product/{productId}', [CostController::class, 'forProduct'])->name('admin.pricing.costs.for-product');

    // Channel Costs
    Route::get('/channel-costs', [ChannelCostController::class, 'index'])->name('admin.pricing.channel-costs.index');
    Route::post('/channel-costs', [ChannelCostController::class, 'store'])->name('admin.pricing.channel-costs.store');
    Route::put('/channel-costs/{id}', [ChannelCostController::class, 'update'])->name('admin.pricing.channel-costs.update');

    // Break-Even Analysis
    Route::get('/break-even/{productId}', [BreakEvenController::class, 'show'])->name('admin.pricing.break-even.show');
    Route::get('/break-even/{productId}/{channelId}', [BreakEvenController::class, 'forChannel'])->name('admin.pricing.break-even.for-channel');

    // Price Recommendations
    Route::get('/recommendations/{productId}', [RecommendationController::class, 'show'])->name('admin.pricing.recommendations.show');
    Route::post('/recommendations/{productId}/apply', [RecommendationController::class, 'apply'])->name('admin.pricing.recommendations.apply');

    // Margin Protection
    Route::get('/margins', [MarginController::class, 'index'])->name('admin.pricing.margins.index');
    Route::get('/margins/{id}', [MarginController::class, 'show'])->name('admin.pricing.margins.show');
    Route::post('/margins/{id}/approve', [MarginController::class, 'approve'])->name('admin.pricing.margins.approve');
    Route::post('/margins/{id}/reject', [MarginController::class, 'reject'])->name('admin.pricing.margins.reject');

    // Pricing Strategies
    Route::get('/strategies', [StrategyController::class, 'index'])->name('admin.pricing.strategies.index');
    Route::get('/strategies/create', [StrategyController::class, 'create'])->name('admin.pricing.strategies.create');
    Route::post('/strategies', [StrategyController::class, 'store'])->name('admin.pricing.strategies.store');
    Route::get('/strategies/{id}/edit', [StrategyController::class, 'edit'])->name('admin.pricing.strategies.edit');
    Route::put('/strategies/{id}', [StrategyController::class, 'update'])->name('admin.pricing.strategies.update');
    Route::delete('/strategies/{id}', [StrategyController::class, 'destroy'])->name('admin.pricing.strategies.destroy');
});
