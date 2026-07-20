<?php

namespace Webkul\Theme\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Webkul\Theme\Themes;
use Webkul\Theme\ThemeViewFinder;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        include __DIR__.'/../Http/helpers.php';

        Blade::directive('unoPimVite', fn ($expression): string => "<?php echo themes()->setUnoPimVite({$expression})->toHtml(); ?>");
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->scoped('themes', fn (): Themes => new Themes);

        $this->app->singleton('view.finder', fn ($app): ThemeViewFinder => new ThemeViewFinder(
            $app['files'],
            $app['config']['view.paths']
        ));
    }
}
