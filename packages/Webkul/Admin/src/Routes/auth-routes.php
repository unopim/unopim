<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Controllers\User\AvatarController;
use Webkul\Admin\Http\Controllers\User\ForgetPasswordController;
use Webkul\Admin\Http\Controllers\User\ResetPasswordController;
use Webkul\Admin\Http\Controllers\User\SessionController;

Route::get('/', [Controller::class, 'redirectToLogin']);

/**
 * Auth routes.
 */
Route::group(['prefix' => config('app.admin_url')], function () {
    /**
     * Public avatar proxy route (no auth middleware).
     */
    Route::get('avatar/u/{hash}.png', [AvatarController::class, 'gravatar'])
        ->name('admin.avatar.public')
        ->middleware('throttle:60,1');

    /**
     * Redirect route.
     */
    Route::get('/', [Controller::class, 'redirectToLogin']);

    Route::controller(SessionController::class)->prefix('login')->group(function () {
        /**
         * Login routes.
         */
        Route::get('', 'create')->name('admin.session.create');

        /**
         * Login post route to admin auth controller.
         */
        Route::post('', 'store')->name('admin.session.store')->middleware('throttle:admin-login');

        /**
         * Microsoft SSO routes.
         */
        Route::get('microsoft', 'redirectToMicrosoft')
            ->name('admin.session.microsoft.redirect')
            ->middleware('throttle:admin-sso');

        Route::get('microsoft/callback', 'handleMicrosoftCallback')
            ->name('admin.session.microsoft.callback')
            ->middleware('throttle:admin-sso');
    });

    /**
     * Forget password routes.
     */
    Route::controller(ForgetPasswordController::class)->prefix('forget-password')->group(function () {
        Route::get('', 'create')->name('admin.forget_password.create');

        Route::post('', 'store')->name('admin.forget_password.store')->middleware('throttle:admin-forgot-password');
    });

    /**
     * Reset password routes.
     */
    Route::controller(ResetPasswordController::class)->prefix('reset-password')->group(function () {
        Route::get('', fn () => redirect()->route('admin.forget_password.create'));

        Route::get('{token}', 'create')->name('admin.reset_password.create');

        Route::post('', 'store')->name('admin.reset_password.store');
    });
});
