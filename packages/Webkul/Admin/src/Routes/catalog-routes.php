<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Catalog\AttributeController;
use Webkul\Admin\Http\Controllers\Catalog\AttributeFamilyController;
use Webkul\Admin\Http\Controllers\Catalog\AttributeGroupController;
use Webkul\Admin\Http\Controllers\Catalog\CategoryController;
use Webkul\Admin\Http\Controllers\Catalog\CategoryFieldController;
use Webkul\Admin\Http\Controllers\Catalog\Options\AjaxOptionsController;
use Webkul\Admin\Http\Controllers\Catalog\ProductController;
use Webkul\Admin\Http\Middleware\EnsureChannelLocaleIsValid;

/**
 * Catalog routes.
 */
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('catalog')->group(function () {
        /**
         * Attributes routes.
         */
        Route::controller(AttributeController::class)->prefix('attributes')->group(function () {
            Route::get('', 'index')->name('admin.catalog.attributes.index');

            Route::get('{id}/options', 'getAttributeOptions')->name('admin.catalog.attributes.options');

            Route::get('create', 'create')->name('admin.catalog.attributes.create');

            Route::post('create', 'store')->name('admin.catalog.attributes.store');

            Route::get('edit/{id}', 'edit')->name('admin.catalog.attributes.edit');

            Route::put('edit/{id}', 'update')->name('admin.catalog.attributes.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.catalog.attributes.delete');

            Route::post('mass-delete', 'massDestroy')->name('admin.catalog.attributes.mass_delete');
        });

        /**
         * Attributes group routes.
         */
        Route::controller(AttributeGroupController::class)->prefix('attributegroups')->group(function () {
            Route::get('', 'index')->name('admin.catalog.attribute.groups.index');

            Route::get('create', 'create')->name('admin.catalog.attribute.groups.create');

            Route::post('create', 'store')->name('admin.catalog.attribute.groups.store');

            Route::get('edit/{id}', 'edit')->name('admin.catalog.attribute.groups.edit');

            Route::put('edit/{id}', 'update')->name('admin.catalog.attribute.groups.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.catalog.attribute.groups.delete');

            Route::post('mass-delete', 'massDestroy')->name('admin.catalog.attribute.groups.mass_delete');
        });

        /**
         * Attribute families routes.
         */
        Route::controller(AttributeFamilyController::class)->prefix('families')->group(function () {
            Route::get('', 'index')->name('admin.catalog.families.index');

            Route::get('create', 'create')->name('admin.catalog.families.create');

            Route::post('create', 'store')->name('admin.catalog.families.store');

            Route::get('edit/{id}', 'edit')->name('admin.catalog.families.edit');

            Route::get('copy/{id}', 'copy')->name('admin.catalog.families.copy');

            Route::put('edit/{id}', 'update')->name('admin.catalog.families.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.catalog.families.delete');
        });

        /**
         * Categories routes.
         */
        Route::controller(CategoryController::class)->prefix('categories')->group(function () {
            Route::get('', 'index')->name('admin.catalog.categories.index');

            Route::get('create', 'create')->name('admin.catalog.categories.create');

            Route::post('create', 'store')->name('admin.catalog.categories.store');

            Route::get('edit/{id}', 'edit')->name('admin.catalog.categories.edit');

            Route::put('edit/{id}', 'update')->name('admin.catalog.categories.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.catalog.categories.delete');

            Route::post('mass-delete', 'massDestroy')->name('admin.catalog.categories.mass_delete');

            Route::get('search', 'search')->name('admin.catalog.categories.search');

            Route::get('tree', 'tree')->name('admin.catalog.categories.tree');
        });

        /**
         * Categories routes.
         */
        Route::controller(CategoryFieldController::class)->prefix('category-fields')->group(function () {
            Route::get('', 'index')->name('admin.catalog.category_fields.index');

            Route::get('create', 'create')->name('admin.catalog.category_fields.create');

            Route::post('create', 'store')->name('admin.catalog.category_fields.store');

            Route::get('edit/{id}', 'edit')->name('admin.catalog.category_fields.edit');

            Route::put('edit/{id}', 'update')->name('admin.catalog.category_fields.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.catalog.category_fields.delete');

            Route::post('mass-delete', 'massDestroy')->name('admin.catalog.category_fields.mass_delete');

            Route::post('mass-update', 'massUpdate')->name('admin.catalog.category_fields.mass_update');

            Route::get('search', 'search')->name('admin.catalog.category_fields.search');

            Route::get('{id}/options', 'getCategoryFieldOptions')->name('admin.catalog.category_fields.options');

            Route::get('tree', 'tree')->name('admin.catalog.category_fields.tree');
        });

        Route::controller(AjaxOptionsController::class)->prefix('ajax-options')->group(function () {
            Route::get('fetch-options', 'getOptions')->name('admin.catalog.options.fetch-all');
        });

        /**
         * Sync route.
         */
        Route::get('/sync', [ProductController::class, 'sync']);

        /**
         * Products routes.
         */
        Route::controller(ProductController::class)->prefix('products')->group(function () {
            Route::get('', 'index')->name('admin.catalog.products.index');

            Route::post('create', 'store')->name('admin.catalog.products.store');

            Route::get('copy/{id}', 'copy')->name('admin.catalog.products.copy');

            Route::get('edit/{id}', 'edit')->name('admin.catalog.products.edit')->middleware(EnsureChannelLocaleIsValid::class);

            Route::put('edit/{id}', 'update')->name('admin.catalog.products.update');

            Route::delete('edit/{id}', 'destroy')->name('admin.catalog.products.delete');

            Route::post('mass-update', 'massUpdate')->name('admin.catalog.products.mass_update');

            Route::post('mass-delete', 'massDestroy')->name('admin.catalog.products.mass_delete');

            Route::get('search', 'search')->name('admin.catalog.products.search');

            Route::post('check-variant', 'checkVariantUniqueness')->name('admin.catalog.products.check-variant');
        });
    });
});
