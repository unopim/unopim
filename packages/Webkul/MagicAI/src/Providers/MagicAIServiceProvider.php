<?php

namespace Webkul\MagicAI\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Webkul\MagicAI\Facades\MagicAI as MagicAIFacade;
use Webkul\MagicAI\MagicAI;

class MagicAIServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        include __DIR__.'/../Http/helpers.php';

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    /**
     * Register services.
     */
    #[\Override]
    public function register(): void
    {
        $loader = AliasLoader::getInstance();

        $loader->alias('magic_ai', MagicAIFacade::class);

        $this->app->singleton('magic_ai', fn () => new MagicAI);

        $this->registerConfig();
    }

    /**
     * Register configuration.
     */
    public function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/default_prompts.php', 'default_prompts');
    }
}
