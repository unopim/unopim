<?php

namespace Webkul\Core\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
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
            return response()->view('admin::errors.index', ['errorCode' => JsonResponse::HTTP_REQUEST_ENTITY_TOO_LARGE]);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle the authentication exception.
     */
    private function handleAuthenticationException(): void
    {
        $this->renderable(function (AuthenticationException $exception, Request $request) {
            if ($request->wantsJson() || $this->isApiRequest($request)) {
                return response()->json([
                    'error' => trans('admin::app.errors.401.message'),
                ], JsonResponse::HTTP_UNAUTHORIZED);
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
            $errorCode = in_array($exception->getStatusCode(), [
                JsonResponse::HTTP_UNAUTHORIZED,
                JsonResponse::HTTP_FORBIDDEN,
                JsonResponse::HTTP_NOT_FOUND,
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                JsonResponse::HTTP_SERVICE_UNAVAILABLE,
            ]) ? $exception->getStatusCode() : JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

            if ($request->wantsJson() || $this->isApiRequest($request)) {
                return response()->json([
                    'error'       => trans("admin::app.errors.{$errorCode}.title"),
                    'description' => trans("admin::app.errors.{$errorCode}.description"),
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
            $errorCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

            if ($request->wantsJson() || $this->isApiRequest($request)) {
                return response()->json([
                    'error'       => trans("admin::app.errors.{$errorCode}.title"),
                    'description' => trans("admin::app.errors.{$errorCode}.description"),
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
            $errorCode = JsonResponse::HTTP_REQUEST_ENTITY_TOO_LARGE;

            if ($request->wantsJson() || $this->isApiRequest($request)) {
                return response()->json([
                    'message'   => trans('admin::app.errors.413.title'),
                    'errorCode' => $errorCode,
                ], $errorCode);
            }

            return response()->view('admin::errors.index', compact('errorCode'));
        });
    }

    /**
     * Determine if the request is an API request.
     */
    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->is('*/api/*');
    }
}
