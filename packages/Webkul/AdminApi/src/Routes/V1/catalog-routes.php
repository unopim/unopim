<?php

use Illuminate\Support\Facades\Route;
use Webkul\AdminApi\Http\Controllers\API\Catalog\AttributeController;
use Webkul\AdminApi\Http\Controllers\API\Catalog\AttributeFamilyController;
use Webkul\AdminApi\Http\Controllers\API\Catalog\AttributeGroupController;
use Webkul\AdminApi\Http\Controllers\API\Catalog\CategoryController;
use Webkul\AdminApi\Http\Controllers\API\Catalog\CategoryFieldController;
use Webkul\AdminApi\Http\Controllers\API\Catalog\ConfigurableProductController;
use Webkul\AdminApi\Http\Controllers\API\Catalog\MediaFileController;
use Webkul\AdminApi\Http\Controllers\API\Catalog\SimpleProductController;

Route::group([
    'middleware' => [
        'auth:api',
    ],
], function () {
    /** Attribute Groups API Routes */
    Route::controller(AttributeGroupController::class)->prefix('attribute-groups')->group(function () {
        Route::get('', 'index')->name('admin.api.attribute_groups.index');
        Route::get('{code}', 'get')->name('admin.api.attribute_groups.get');
        Route::post('', 'store')->name('admin.api.attribute_groups.store');
        Route::put('{code}', 'update')->name('admin.api.attribute_groups.update');
    });

    /** Attributes API Routes */
    Route::controller(AttributeController::class)->prefix('attributes')->group(function () {
        Route::get('', 'index')->name('admin.api.attributes.index');
        Route::get('{code}', 'get')->name('admin.api.attributes.get');
        Route::post('', 'store')->name('admin.api.attributes.store');
        Route::put('{code}', 'update')->name('admin.api.attributes.update');

        Route::get('{code}/options', 'getOptions')->name('admin.api.attribute_options.get');
        Route::post('{code}/options', 'storeOption')->name('admin.api.attribute_options.store_option');
        Route::put('{code}/options', 'updateOption')->name('admin.api.attribute_options.update_option');
    });

    /** Attributes Family API Routes */
    Route::controller(AttributeFamilyController::class)->prefix('families')->group(function () {
        Route::get('', 'index')->name('admin.api.families.index');
        Route::get('{code}', 'get')->name('admin.api.families.get');
        Route::post('', 'store')->name('admin.api.families.store');
        Route::put('{code}', 'update')->name('admin.api.families.update');
    });

    /** Category Fields API Routes */
    Route::controller(CategoryFieldController::class)->prefix('category-fields')->group(function () {
        Route::get('', 'index')->name('admin.api.category-fields.index');
        Route::get('{code}', 'get')->name('admin.api.category-fields.get');
        Route::post('', 'store')->name('admin.api.category-fields.store');
        Route::put('{code}', 'update')->name('admin.api.category-fields.update');

        Route::get('{code}/options', 'getOptions')->name('admin.api.category-fields_options.get');
        Route::post('{code}/options', 'storeOption')->name('admin.api.category-fields-options.store_option');
        Route::put('{code}/options', 'updateOption')->name('admin.api.category-fields-options.update_option');
    });

    /** Categories API Routes */
    Route::controller(CategoryController::class)->prefix('categories')->group(function () {
        Route::get('', 'index')->name('admin.api.categories.index');
        Route::get('{code}', 'get')->name('admin.api.categories.get');
        Route::post('', 'store')->name('admin.api.categories.store');
        Route::put('{code}', 'update')->name('admin.api.categories.update');
    });

    /** Media API Routes */
    Route::controller(MediaFileController::class)->prefix('media-files')->group(function () {
        // Route::get('', 'index')->name('admin.api.categories.index');
        // Route::get('{code}', 'get')->name('admin.api.categories.get');
        Route::prefix('category')->group(function () {
            Route::post('', 'storeCategoryMedia')->name('admin.api.media-files.category.store');
        });

        Route::prefix('product')->group(function () {
            Route::post('', 'storeProductMedia')->name('admin.api.media-files.product.store');
        });
    });

    /** Products API Routes */
    Route::controller(SimpleProductController::class)->prefix('products')->group(function () {
        Route::get('', 'index')->name('admin.api.products.index');
        Route::get('{code}', 'get')->name('admin.api.products.get');
        Route::post('', 'store')->name('admin.api.products.store');
        Route::put('{code}', 'update')->name('admin.api.products.update');
        Route::delete('{code}', 'delete')->name('admin.api.products.delete');
    });

    /** Configurable Products API Routes */
    Route::controller(ConfigurableProductController::class)->prefix('configrable-products')->group(function () {
        Route::get('', 'index')->name('admin.api.configrable_products.index');
        Route::get('{code}', 'get')->name('admin.api.configrable_products.get');
        Route::post('', 'store')->name('admin.api.configrable_products.store');
        Route::put('{code}', 'update')->name('admin.api.configrable_products.update');
    });
});
