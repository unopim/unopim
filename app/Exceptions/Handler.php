<?php

namespace App\Exceptions;

use Dotenv\Exception\InvalidFileException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof PostTooLargeException) {
            if ($request->ajax()) {
                return response()->json([
                    'message'   => trans('admin::app.errors.413.title'),
                    'errorCode' => $exception->getStatusCode() ?? 413,
                ], $exception->getStatusCode() ?? 413);
            }

            return response()->view('admin::errors.index', ['errorCode' => $exception->getStatusCode() ?? 413]);
        }

        if ($exception instanceof InvalidFileException) {
            if ($request->ajax()) {
                return response()->json([
                    'message'   => $exception->getMessage(),
                ], 500);
            }

            exit($exception->getMessage());
        }

        return parent::render($request, $exception);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
