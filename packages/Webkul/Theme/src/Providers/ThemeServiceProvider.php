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

        Blade::directive('unoPimVite', fn (string $expression) => "<?php echo themes()->setUnoPimVite({$expression})->toHtml(); ?>");
    }

    /**
     * Register services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton('themes', fn () => new Themes);

        $this->app->singleton('view.finder', fn (mixed $app) => new ThemeViewFinder(
            $app['files'],
            $app['config']['view.paths']
        ));
    }
}
