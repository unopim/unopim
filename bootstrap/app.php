<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Webkul\Admin\Http\Middleware\ConvertAjaxFormRedirect;
use Webkul\Core\Http\Middleware\CheckForMaintenanceMode;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Core\Http\Middleware\SecureHeaders;
use Webkul\Installer\Http\Middleware\CanInstall;

$toList = static fn (?string $value): array => array_values(array_unique(array_filter(
    array_map(trim(...), explode(',', (string) $value))
)));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->booting(function () use ($toList): void {
        $proxies = $toList(config('app.trusted_proxies'));

        TrustProxies::at(in_array('*', $proxies, true) ? '*' : $proxies);
    })
    ->withMiddleware(function (Middleware $middleware) use ($toList): void {
        $middleware->trustHosts(at: fn (): array => $toList(config('app.trusted_hosts')));

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
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->onOneServer()
            ->withoutOverlapping()
            ->runInBackground()
            ->group(function () use ($schedule): void {
                $schedule->command('unopim:product:index')->twiceDailyAt(0, 12, 1);
                $schedule->command('unopim:category:index')->twiceDailyAt(0, 12, 1);
                $schedule->command('unopim:completeness:recalculate', ['--all'])->dailyAt('02:00');
                $schedule->command('unopim:dashboard:refresh')->everyTenMinutes();
            });
    })
    ->withExceptions()
    ->create();
