<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Controllers\User\AvatarController;
use Webkul\Admin\Http\Controllers\User\ForgetPasswordController;
use Webkul\Admin\Http\Controllers\User\ResetPasswordController;
use Webkul\Admin\Http\Controllers\User\SessionController;
use Webkul\Admin\Http\Controllers\User\SsoController;

Route::get('/', [Controller::class, 'redirectToLogin']);

/**
 * Auth routes.
 */
Route::group(['prefix' => config('app.admin_url')], function () {
    /**
     * Public avatar proxy route (no auth middleware).
     */
    Route::get('avatar/u/{hash}', [AvatarController::class, 'gravatar'])
        ->where('hash', '[a-f0-9]{32}')
        ->name('admin.avatar.public')
        ->middleware('throttle:120,1');

    /**
     * Redirect route.
     */
    Route::get('/', [Controller::class, 'redirectToLogin']);

    Route::prefix('login')->group(function () {
        Route::controller(SessionController::class)->group(function () {
            /**
             * Login routes.
             */
            Route::get('', 'create')->name('admin.session.create');

            /**
             * Login post route to admin auth controller.
             */
            Route::post('', 'store')->name('admin.session.store')->middleware('throttle:admin-login');
        });

        Route::controller(SsoController::class)->middleware('throttle:admin-sso')->group(function () {
            /**
             * SSO routes for registered drivers.
             */
            Route::get('sso/{provider}', 'redirect')
                ->where('provider', '[a-z0-9_-]+')
                ->name('admin.session.sso.redirect');

            Route::get('sso/{provider}/callback', 'callback')
                ->where('provider', '[a-z0-9_-]+')
                ->name('admin.session.sso.callback');
        });
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

        Route::post('', 'store')->name('admin.reset_password.store')->middleware('throttle:admin-reset-password');
    });
});
