<?php

use Dotenv\Exception\InvalidFileException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Routing\Middleware\SubstituteBindings;
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
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: ['sidebar_collapsed', 'dark_mode']);
        $middleware->trimStrings(except: ['current_password', 'password', 'password_confirmation']);
        $middleware->append([
            SecureHeaders::class,
            NoCacheMiddleware::class,
            CheckForMaintenanceMode::class,
            CanInstall::class,
        ]);
        $middleware->api(remove: [
            SubstituteBindings::class,
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

        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation']);
    })
    ->create();
