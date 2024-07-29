<?php

namespace Webkul\Core\Exceptions;

use App\Exceptions\Handler as BaseHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends BaseHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        if (config('app.debug')) {
            return;
        }

        $this->handleAuthenticationException();

        $this->handleHttpException();

        $this->handleValidationException();

        $this->handleServerException();

        $this->handlePostTooLargeException();
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof PostTooLargeException) {
            return response()->view('admin::errors.index', ['errorCode' => $exception->getStatusCode() ?? 413]);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle the authentication exception.
     */
    private function handleAuthenticationException(): void
    {
        $this->renderable(function (AuthenticationException $exception, Request $request) {
            $path = $request->is(config('app.admin_url').'/*') ? 'admin' : 'api';

            if ($request->wantsJson()) {
                return response()->json(['error' => trans('admin::app.errors.401.message')], 401);
            }

            if ($path !== 'admin') {
                return redirect()->guest(route('shop.customer.session.index'));
            }

            return redirect()->guest(route('admin.session.create'));
        });
    }

    /**
     * Handle the http exceptions.
     */
    private function handleHttpException(): void
    {
        $this->renderable(function (HttpException $exception, Request $request) {

            $errorCode = in_array($exception->getStatusCode(), [401, 419, 403, 404, 503])
                ? $exception->getStatusCode()
                : 500;

            if ($request->wantsJson()) {
                return response()->json([
                    'error'       => trans("admin::app.errors.{$errorCode}.title"),
                    'description' => trans("admin::app.shop.errors.{$errorCode}.description"),
                ], $errorCode);
            }

            return response()->view('admin::errors.index', compact('errorCode'));
        });
    }

    /**
     * Handle the server exceptions.
     */
    private function handleServerException(): void
    {
        $this->renderable(function (Throwable $throwable, Request $request) {

            $errorCode = 500;

            if ($request->wantsJson()) {
                return response()->json([
                    'error'       => trans("admin::app.errors.{$errorCode}.title"),
                    'description' => trans("admin::app.shop.errors.{$errorCode}.description"),
                ], $errorCode);
            }

            return response()->view('admin::errors.index', compact('errorCode'));
        });
    }

    /**
     * Handle validation exceptions.
     */
    private function handleValidationException(): void
    {
        $this->renderable(function (ValidationException $exception, Request $request) {
            return parent::convertValidationExceptionToResponse($exception, $request);
        });
    }

    /**
     * Handle postTooLarge Exception exceptions.
     */
    private function handlePostTooLargeException(): void
    {
        $this->renderable(function (PostTooLargeException $exception, Request $request) {
            $errorCode = $exception->getStatusCode() ?? 413;

            return response()->view('admin::errors.index', compact('errorCode'));
        });
    }
}
