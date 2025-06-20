<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\DashboardController;
use Webkul\Admin\Http\Controllers\DataGridController;
use Webkul\Admin\Http\Controllers\MagicAIController;
use Webkul\Admin\Http\Controllers\ManageColumnController;
use Webkul\Admin\Http\Controllers\TinyMCEController;
use Webkul\Admin\Http\Controllers\User\AccountController;
use Webkul\Admin\Http\Controllers\User\SessionController;
use Webkul\Admin\Http\Controllers\VueJsSelect\SelectOptionsController;
use Webkul\HistoryControl\Http\Controllers\HistoryController;

/**
 * Extra routes.
 */
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    /**
     * Dashboard routes.
     */
    Route::controller(DashboardController::class)->prefix('dashboard')->group(function () {
        Route::get('', 'index')->name('admin.dashboard.index');

        Route::get('stats', 'stats')->name('admin.dashboard.stats');
    });

    /**
     * Datagrid routes.
     */
    Route::get('datagrid/look-up', [DataGridController::class, 'lookUp'])->name('admin.datagrid.look_up');

    /**
     * Available Columns routes.
     */
    Route::get('datagrid/available-columns', [ManageColumnController::class, 'availableColumns'])->name('admin.datagrid.available_columns');

    /**
     * Tinymce file upload handler.
     */
    Route::post('tinymce/upload', [TinyMCEController::class, 'upload'])->name('admin.tinymce.upload');

    /**
     * AI Routes
     */
    Route::controller(MagicAIController::class)->prefix('magic-ai')->group(function () {
        Route::get('model', 'model')->name('admin.magic_ai.model');

        Route::get('validate-credential', 'validateCredential')->name('admin.magic_ai.validate_credential');

        Route::get('available-model', 'availableModel')->name('admin.magic_ai.available_model');

        Route::get('suggestion-values', 'suggestionValues')->name('admin.magic_ai.suggestion_values');

        Route::post('content', 'content')->name('admin.magic_ai.content');

        Route::post('image', 'image')->name('admin.magic_ai.image');

        Route::get('default-prompt', 'defaultPrompt')->name('admin.magic_ai.default_prompt');

        Route::get('prompt', 'index')->name('admin.magic_ai.prompt.index');

        Route::post('create-prompt', 'store')->name('admin.magic_ai.prompt.store');

        Route::delete('delete\{id}', 'destroy')->name('admin.magic_ai.prompt.delete');

        Route::get('edit\{id}', 'edit')->name('admin.magic_ai.prompt.edit');

        Route::put('edit', 'update')->name('admin.magic_ai.prompt.update');

        Route::post('translate', 'translateToManyLocale')->name('admin.magic_ai.translate');

        Route::post('check/isTranslatable', 'isTranslatable')->name('admin.magic_ai.check.is_translatable');

        Route::post('save/translatedData', 'saveTranslatedData')->name('admin.magic_ai.store.translated');

        Route::post('check/is-all-attribute-translatable', 'isAllAttributeTranslatable')->name('admin.magic_ai.check.is_all_attribute_translatable');

        Route::post('translate/all/attribute', 'translateAllAttribute')->name('admin.magic_ai.translate.all.attribute');

        Route::post('save/translated-attributes', 'saveAllTranslatedAttributes')->name('admin.magic_ai.store.translated.all_attribute');

    });

    /**
     * Admin profile routes.
     */
    Route::controller(AccountController::class)->prefix('account')->group(function () {
        Route::get('', 'edit')->name('admin.account.edit');

        Route::put('', 'update')->name('admin.account.update');
    });

    /**
     * History routes.
     */
    Route::controller(HistoryController::class)->prefix('history')->group(function () {
        Route::get('view/{entity}/{id}', 'get')->name('admin.history.index');

        Route::get('view/{entity}/{id}/{historyId}', 'getHistoryView')->name('admin.history.view');

        Route::get('view-version/{entity}/{id}/{versionId}', 'getVersionHistoryView')->name('admin.history.version.view');

        Route::post('view-version/{entity}/{id}/{versionId}', 'restoreHistory')->name('admin.history.version.restore');

        Route::delete('view-version/{entity}/{id}/{versionId}', 'deleteHistory')->name('admin.history.version.delete');
    });

    Route::delete('logout', [SessionController::class, 'destroy'])->name('admin.session.destroy');

    /**
     * Select options routes.
     */
    Route::get('vue-js-select/select-options', [SelectOptionsController::class, 'getOptions'])->name('admin.vue_js_select.select.options');
});
