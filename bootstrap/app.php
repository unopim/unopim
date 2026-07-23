<?php

use Dotenv\Exception\InvalidFileException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webkul\Admin\Http\Middleware\ConvertAjaxFormRedirect;
use Webkul\Core\Http\Middleware\CheckForMaintenanceMode;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Core\Http\Middleware\SecureHeaders;
use Webkul\Installer\Http\Middleware\CanInstall;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
         * Defence in depth against Host / X-Forwarded-Host header
         * poisoning: only honour hosts that match APP_URL (or extra
         * hosts in TRUSTED_HOSTS). Symfony returns 400 for any other
         * host. The closure runs lazily, after config is bound.
         */
        $middleware->trustHosts(at: function () {
            $hosts = [];

            if ($appHost = parse_url((string) config('app.url'), PHP_URL_HOST)) {
                $hosts[] = $appHost;
            }

            $extra = array_filter(array_map('trim', explode(',', (string) env('TRUSTED_HOSTS', ''))));

            return array_values(array_unique(array_merge($hosts, $extra)));
        });

        /*
         * Restrict trusted proxies to TRUSTED_PROXIES (comma-separated).
         * Falls back to the loopback address when unset so production
         * deployments behind a load balancer must opt in explicitly.
         */
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '127.0.0.1'));

        $middleware->encryptCookies(except: ['sidebar_collapsed', 'dark_mode']);
        $middleware->trimStrings(except: ['current_password', 'password', 'password_confirmation']);
        $middleware->append([
            SecureHeaders::class,
            NoCacheMiddleware::class,
            CheckForMaintenanceMode::class,
            CanInstall::class,
        ]);

        $middleware->web(append: [
            ConvertAjaxFormRedirect::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Elasticsearch re-indexing (twice daily)
        $schedule->command('unopim:product:index')->dailyAt('00:01');
        $schedule->command('unopim:product:index')->dailyAt('12:01');
        $schedule->command('unopim:category:index')->dailyAt('00:01');
        $schedule->command('unopim:category:index')->dailyAt('12:01');

        // Completeness recalculation (daily at 2 AM)
        $schedule->command('unopim:completeness:recalculate --all')->dailyAt('02:00');

        // Dashboard cache refresh (every 10 minutes)
        $schedule->command('unopim:dashboard:refresh')->everyTenMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (PostTooLargeException $e, $request) {
            $errorCode = $e->getStatusCode() ?? 413;

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message'   => trans('admin::app.errors.413.title'),
                    'errorCode' => $errorCode,
                ], $errorCode);
            }

            return response()->view('admin::errors.index', ['errorCode' => $errorCode]);
        });

        $exceptions->render(function (InvalidFileException $e, $request) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 500);
            }

            exit($e->getMessage());
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            $errorCode = 404;

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message'   => trans('admin::app.errors.'.$errorCode.'.title'),
                    'errorCode' => $errorCode,
                ], $errorCode);
            }

            return response()->view('admin::errors.index', ['errorCode' => $errorCode], $errorCode);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            $errorCode = 405;
            $headers = $e->getHeaders();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message'   => trans('admin::app.errors.'.$errorCode.'.title'),
                    'errorCode' => $errorCode,
                ], $errorCode, $headers);
            }

            return response()->view('admin::errors.index', ['errorCode' => $errorCode], $errorCode, $headers);
        });

        /*
         * Render rate-limit lockouts (e.g. the admin login throttle) as the styled
         * 429 page / a structured JSON body instead of the generic 500. Only active
         * when APP_DEBUG is off, so local debugging still sees the raw exception.
         */
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if (config('app.debug')) {
                return null;
            }

            $errorCode = 429;
            $headers = $e->getHeaders();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'error'       => trans('admin::app.errors.'.$errorCode.'.title'),
                    'description' => trans('admin::app.errors.'.$errorCode.'.description'),
                ], $errorCode, $headers);
            }

            return response()->view('admin::errors.index', ['errorCode' => $errorCode], $errorCode, $headers);
        });

        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation']);
    })
    ->create();
